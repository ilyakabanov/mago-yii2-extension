<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Parity;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RuntimeException;
use yii2\extensions\mago\ServiceMap;
use yii2\extensions\mago\StubGenerator;

/** Pure source transformation: returns artifacts; the caller chooses how to persist them. */
final class FixtureBuilder
{
    /** @return array<string, string> */
    public function build(string $root): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder();
        $expected = [];
        $manifest = [];
        $artifacts = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/tests/reference'));
        $paths = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);
        foreach ($paths as $path) {
            $source = file_get_contents($path);
            $nodes = $parser->parse($source);
            $traverser = new NodeTraverser(new NameResolver());
            $nodes = $traverser->traverse($nodes);
            $calls = $finder->find($nodes, static fn (Node $node): bool => $node instanceof Expr\FuncCall && $node->name instanceof Name
                && in_array($node->name->toString(), ['PHPStan\\Testing\\assertType', 'PHPStan\\Testing\\assertSuperType'], true));
            if ($calls === []) {
                continue;
            }

            $relative = substr($path, strlen($root . '/tests/reference/'));
            $profile = $this->profile($relative);
            $replacements = [];
            foreach ($calls as $call) {
                $original = $this->value($call->args[0]->value);
                $normalized = str_replace('*NEVER*', 'never', $original);
                if (str_contains(substr($source, $call->args[0]->value->getStartFilePos(), $call->args[0]->value->getEndFilePos() - $call->args[0]->value->getStartFilePos() + 1), 'PHPSTAN_TEST_LOG_PAYLOAD_TYPE')) {
                    $normalized = str_replace('array<mixed>|string|Throwable', '\\Parity\\LogPayload', $normalized);
                }
                $normalized = preg_replace_callback('/static\(([^()]+)\)/', static fn (array $match): string => '\\Parity\\StaticType<' . $match[1] . '>', $normalized);
                $hash = substr(hash('sha256', $normalized), 0, 20);
                $function = 'parity_expected_' . $hash;
                $expected[$function] = $normalized;
                $kind = str_ends_with($call->name->toString(), 'assertSuperType') ? 'assertSuperType' : 'assertType';
                $argument = $call->args[1]->value;
                $expression = substr($source, $argument->getStartFilePos(), $argument->getEndFilePos() - $argument->getStartFilePos() + 1);
                $replacement = '\\Parity\\' . $kind . '(' . $expression . ', \\' . $function . '())';
                $replacements[] = [$call->getStartFilePos(), $call->getEndFilePos() + 1, $replacement];
                $manifest[] = [
                    'file' => $relative, 'line' => $call->getStartLine(), 'profile' => $profile,
                    'kind' => $kind, 'expected' => $original, 'nativeExpected' => $normalized,
                    'normalizations' => $original === $normalized ? [] : ['PHPStan static(Class) uses a StaticType<Class> marker decoded into Mago late-static metadata; *NEVER* becomes never'],
                ];
            }
            usort($replacements, static fn (array $left, array $right): int => $right[0] <=> $left[0]);
            foreach ($replacements as [$start, $end, $replacement]) {
                $source = substr_replace($source, $replacement, $start, $end - $start);
            }
            $artifacts['tests/Parity/fixtures/' . $profile . '/' . $relative] = $source;
        }

        ksort($expected);
        $functions = "<?php\n\ndeclare(strict_types=1);\n\n";
        foreach ($expected as $function => $type) {
            if (str_contains($type, '*/')) {
                throw new RuntimeException('Expected type cannot be represented in a PHPDoc: ' . $type);
            }
            $functions .= '/** @return \\Parity\\ExpectedType<' . $type . "> */\nfunction " . $function . "(): mixed { throw new \\LogicException(); }\n\n";
        }
        $artifacts['tests/Parity/expected.php'] = $functions;
        $artifacts['tests/Parity/manifest.json'] = json_encode(['fixtures' => count($artifacts) - 1, 'assertions' => count($manifest), 'cases' => $manifest], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        require_once __DIR__ . '/reference-autoload.php';
        foreach (Profiles::CONFIGS as $profile => $config) {
            $artifacts['tests/Parity/stubs/' . $profile . '/Yii.php'] = StubGenerator::generate(new ServiceMap($root . '/tests/reference/config/' . $config));
            $configuration = "php-version = \"8.3\"\n\n[source]\npaths = [\"" . ($profile === 'self-test' ? 'Parity/self-test.php' : 'Parity/fixtures/' . ($profile === 'header' ? 'web' : $profile)) . "\"]\n";
            $configuration .= 'includes = ["Parity/functions.php", "Parity/expected.php", "reference/support/stub", "../vendor/yiisoft/yii2", "../vendor/phpunit/phpunit/src/Framework/Attributes"]' . "\n";
            $configuration .= 'patches = ["Parity/stubs/' . $profile . '/Yii.php"]' . "\n\n[analyzer]\nfind-unused-definitions = false\nfind-unused-expressions = false\n\n[extension-hosts.yii2]\ncommand = [\"php\", \"../../parity-worker.php\"]\n";
            $artifacts['tests/Parity/config/' . $profile . '.toml'] = $configuration;
        }

        return $artifacts;
    }

    private function profile(string $path): string
    {
        if (str_starts_with($path, 'console/')) { return 'console'; }
        if (str_starts_with($path, 'custom/')) { return 'custom'; }
        if (str_ends_with($path, '/ParamsAwkwardKeysType.php')) { return 'awkward'; }
        if (str_contains($path, 'ApplicationParams')) { return 'params'; }
        if (str_ends_with($path, '/ServiceMapDefinitionType.php')) { return 'definition'; }
        return 'web';
    }

    private function value(Expr $expression): string
    {
        if ($expression instanceof String_) { return $expression->value; }
        if ($expression instanceof Expr\BinaryOp\Concat) { return $this->value($expression->left) . $this->value($expression->right); }
        if ($expression instanceof Expr\ClassConstFetch && $expression->class instanceof Name && $expression->name instanceof Node\Identifier && strtolower($expression->name->toString()) === 'class') {
            return $expression->class->toString();
        }
        if ($expression instanceof Expr\ConstFetch && $expression->name->getLast() === 'PHPSTAN_TEST_LOG_PAYLOAD_TYPE') {
            return 'array<mixed>|string|Throwable';
        }
        throw new RuntimeException('Unsupported expected-type expression: ' . $expression->getType() . ' on line ' . $expression->getStartLine());
    }
}

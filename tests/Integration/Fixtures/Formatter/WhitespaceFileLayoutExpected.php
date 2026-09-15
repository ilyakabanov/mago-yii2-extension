<?php

return implode("\n", [
    '<?php',
    '',
    'namespace Fixture;',
    '',
    'final class WhitespaceFileLayout',
    '{',
    '    public function value(): string',
    '    {',
    "        return 'value';",
    '    }',
    '}',
]) . "\n";

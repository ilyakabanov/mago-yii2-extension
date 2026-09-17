<?php

declare(strict_types=1);

return [
    'versions' => [
        'phpcs' => '4.0.4',
        'yii2-coding-standards' => '3.0.2',
        'mago' => '1.47.1',
    ],
    'diagnostic-rules' => [
        'PSR1.Classes.ClassDeclaration.MissingNamespace' => ['require-namespace'],
        'PSR1.Classes.ClassDeclaration.MultipleClasses' => ['single-class-per-file'],
    ],
    'preferred-fixtures' => [
        'Generic.Formatting.DisallowMultipleStatements' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Generic.Functions.FunctionCallArgumentSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Generic.WhiteSpace.IncrementDecrementSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR2.Classes.ClassDeclaration' => [
            'tests/Integration/Fixtures/Formatter/Yii2CodingStandardsInput.php',
        ],
        'PSR2.Methods.FunctionCallSignature' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR2.Methods.FunctionClosingBrace' => [
            'tests/Integration/Fixtures/Formatter/FunctionDeclarationLayoutInput.php',
        ],
        'PSR12.Classes.AnonClassDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.Classes.ClassInstantiation' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.Classes.OpeningBraceSpace' => [
            'tests/Integration/Fixtures/Formatter/Yii2CodingStandardsInput.php',
        ],
        'PSR12.ControlStructures.BooleanOperatorPlacement' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.ControlStructures.ControlStructureSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.Files.DeclareStatement' => [
            'tests/Integration/Fixtures/Formatter/FileHeaderLayoutInput.php',
        ],
        'PSR12.Files.FileHeader' => [
            'tests/Integration/Fixtures/Formatter/FileHeaderLayoutInput.php',
        ],
        'PSR12.Functions.NullableTypeDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FunctionDeclarationLayoutInput.php',
        ],
        'PSR12.Functions.ReturnTypeDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FunctionDeclarationLayoutInput.php',
        ],
        'PSR12.Namespaces.CompoundNamespaceDepth' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.Operators.OperatorSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'PSR12.Traits.UseDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.ControlStructures.ControlSignature' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.ControlStructures.ForEachLoopDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.ControlStructures.ForLoopDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.Functions.FunctionDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FunctionDeclarationLayoutInput.php',
        ],
        'Squiz.Functions.FunctionDeclarationArgumentSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.Functions.MultiLineFunctionDeclaration' => [
            'tests/Integration/Fixtures/Formatter/FunctionDeclarationLayoutInput.php',
        ],
        'Squiz.Strings.ConcatenationSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.Strings.DoubleQuoteUsage' => [
            'tests/Integration/Fixtures/Formatter/Input.php',
        ],
        'Squiz.WhiteSpace.ControlStructureSpacing' => [
            'tests/Integration/Fixtures/Formatter/FormatterCoveredRulesInput.php',
        ],
        'Squiz.WhiteSpace.ScopeClosingBrace' => [
            'tests/Integration/Fixtures/Formatter/Yii2CodingStandardsInput.php',
        ],
        'Squiz.WhiteSpace.ScopeKeywordSpacing' => [
            'tests/Integration/Fixtures/Formatter/Yii2CodingStandardsInput.php',
        ],
    ],
    'sniffs' => [
        'Generic.Arrays.DisallowLongArraySyntax' => ['array-style'],
        'Generic.ControlStructures.InlineControlStructure' => ['block-statement'],
        'Generic.Files.ByteOrderMark' => ['yii2/byte-order-mark'],
        'Generic.Files.LineEndings' => [],
        'Generic.Formatting.DisallowMultipleStatements' => [],
        'Generic.Functions.FunctionCallArgumentSpacing' => [],
        'Generic.NamingConventions.UpperCaseConstantName' => ['constant-name'],
        'Generic.PHP.DisallowAlternativePHPTags' => ['yii2/disallow-alternative-php-tags'],
        'Generic.PHP.DisallowShortOpenTag' => ['no-short-opening-tag'],
        'Generic.PHP.LowerCaseConstant' => ['lowercase-keyword'],
        'Generic.PHP.LowerCaseKeyword' => ['lowercase-keyword'],
        'Generic.PHP.LowerCaseType' => ['lowercase-type-hint'],
        'Generic.WhiteSpace.DisallowTabIndent' => [],
        'Generic.WhiteSpace.IncrementDecrementSpacing' => [],
        'Generic.WhiteSpace.ScopeIndent' => [],
        'PEAR.Functions.ValidDefaultValue' => ['optional-param-order'],
        'PSR1.Classes.ClassDeclaration' => ['require-namespace', 'single-class-per-file'],
        'PSR1.Files.SideEffects' => ['no-side-effects-with-declarations'],
        'PSR1.Methods.CamelCapsMethodName' => ['method-name'],
        'PSR2.Classes.ClassDeclaration' => [],
        'PSR2.Classes.PropertyDeclaration' => ['yii2/property-declaration'],
        'PSR2.ControlStructures.ElseIfDeclaration' => ['yii2/else-if-declaration'],
        'PSR2.Files.ClosingTag' => ['no-closing-tag'],
        'PSR2.Files.EndFileNewline' => [],
        'PSR2.Methods.FunctionCallSignature' => [],
        'PSR2.Methods.FunctionClosingBrace' => [],
        'PSR2.Methods.MethodDeclaration' => ['yii2/method-declaration'],
        'PSR12.Classes.AnonClassDeclaration' => [],
        'PSR12.Classes.ClassInstantiation' => [],
        'PSR12.Classes.ClosingBrace' => ['yii2/closing-brace'],
        'PSR12.Classes.OpeningBraceSpace' => [],
        'PSR12.ControlStructures.BooleanOperatorPlacement' => [],
        'PSR12.ControlStructures.ControlStructureSpacing' => [],
        'PSR12.Files.DeclareStatement' => [],
        'PSR12.Files.FileHeader' => ['yii2/file-header'],
        'PSR12.Files.ImportStatement' => ['yii2/import-statement'],
        'PSR12.Files.OpenTag' => [],
        'PSR12.Functions.NullableTypeDeclaration' => [],
        'PSR12.Functions.ReturnTypeDeclaration' => [],
        'PSR12.Keywords.ShortFormTypeKeywords' => ['yii2/short-form-type-keywords'],
        'PSR12.Namespaces.CompoundNamespaceDepth' => [],
        'PSR12.Operators.OperatorSpacing' => [],
        'PSR12.Properties.ConstantVisibility' => ['yii2/constant-visibility'],
        'PSR12.Traits.UseDeclaration' => ['yii2/trait-use-declaration'],
        'Squiz.Classes.ValidClassName' => ['class-name'],
        'Squiz.ControlStructures.ControlSignature' => [],
        'Squiz.ControlStructures.ForEachLoopDeclaration' => [],
        'Squiz.ControlStructures.ForLoopDeclaration' => [],
        'Squiz.ControlStructures.LowercaseDeclaration' => ['lowercase-keyword'],
        'Squiz.Functions.FunctionDeclaration' => [],
        'Squiz.Functions.FunctionDeclarationArgumentSpacing' => [],
        'Squiz.Functions.LowercaseFunctionKeywords' => ['lowercase-keyword'],
        'Squiz.Functions.MultiLineFunctionDeclaration' => [],
        'Squiz.NamingConventions.ValidVariableName' => ['yii2/private-property-underscore'],
        'Squiz.Scope.MethodScope' => ['yii2/method-scope'],
        'Squiz.Strings.ConcatenationSpacing' => [],
        'Squiz.Strings.DoubleQuoteUsage' => [],
        'Squiz.WhiteSpace.CastSpacing' => ['yii2/cast-spacing'],
        'Squiz.WhiteSpace.ControlStructureSpacing' => [],
        'Squiz.WhiteSpace.ScopeClosingBrace' => [],
        'Squiz.WhiteSpace.ScopeKeywordSpacing' => [],
        'Squiz.WhiteSpace.SuperfluousWhitespace' => ['no-trailing-space'],
    ],
];

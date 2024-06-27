<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;


return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpVersion(Rector\ValueObject\PhpVersion::PHP_83)
    ->withPhpSets()
    ->withPreparedSets(
        codingStyle: true, 
        codeQuality: true, 
        earlyReturn: true,
        typeDeclarations: true, 
        // strictBooleans: true,
        rectorPreset: true,
    )
    ->withConfiguredRule(Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector::class, [
        new Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration('ArrayAccess', 'offsetExists', new PHPStan\Type\BooleanType()),
        new Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration('ArrayAccess', 'offsetSet', new PHPStan\Type\VoidType()),
        new Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration('ArrayAccess', 'offsetUnset', new PHPStan\Type\VoidType()),

    ])
    ->withRules([
        // // Early returns
        // Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector::class,
        // Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector::class,
        // Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector::class,
        // Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector::class,
        // Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector::class,
        // Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector::class,

        // // Strict types
        // Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector::class,

        // // TypeDeclaration
        // Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector::class,
        // Rector\Transform\Rector\ClassMethod\ReturnTypeWillChangeRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\AddTypeFromResourceDocblockRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromStrictScalarReturnsRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictScalarReturnsRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnCastRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector::class,
        // Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector::class,
    ]);

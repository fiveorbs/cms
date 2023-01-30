<?php

declare(strict_types=1);

use Conia\Core\Exception\ParserException;
use Conia\Core\Finder\QueryLexer;
use Conia\Core\Tests\Setup\TestCase;

uses(TestCase::class);

const QUERY_ALL_ELEMENTS = '(true = field1 & builtin1 > now & null >= 13 & field2 < "string") |' .
    '(13.73 <= builtin2 | field3 ~ "%string" | builtin3 !~"string%" | path.de-DE != 31 | ' .
    ' path !~~ \'url\' & field4 ~~ \'%str%\')';

test('Simple query', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens('field = test');

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('Field');
});

test('Simple query with single quote string', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens("field = 'test'");

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('String');
});

test('Simple query with double quote string', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens('field = "test"');

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('String');
});

test('Simple query with single quote string and escape', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens("field = '\"test\"\\'string\\'test'");

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('String');
    expect($tokens[2]->lexeme)->toBe('"test"\'string\'test');
});

test('Simple query with double quote string and escape', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens('field = "\'test\'\\"string\\"test"');

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('String');
    expect($tokens[2]->lexeme)->toBe("'test'\"string\"test");
});

test('Unterminated string', function () {
    $lexer = new QueryLexer();
    $lexer->tokens('field = "test');
})->throws(ParserException::class, 'Unterminated string');

test('Invalid operator', function () {
    $lexer = new QueryLexer();
    $lexer->tokens('field !- test');
})->throws(ParserException::class, 'Invalid operator');

test('Syntax error', function () {
    $lexer = new QueryLexer();
    $lexer->tokens('field # test');
})->throws(ParserException::class, 'Syntax error');

test('Invalid number', function () {
    $lexer = new QueryLexer();
    $lexer->tokens('field = 10.');
})->throws(ParserException::class, 'Invalid number');

test('Syntax error (special case minus)', function () {
    // We need to test minus separately as a minus starts
    // the number parser.
    $lexer = new QueryLexer();
    $lexer->tokens('field - test');
})->throws(ParserException::class, 'Syntax error');

test('And with grouped or query', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens('field = "test" & (name.de = "test" | name.en = "test") ');

    expect($tokens[0]->type->name)->toBe('Field');
    expect($tokens[1]->type->name)->toBe('Equal');
    expect($tokens[2]->type->name)->toBe('String');

    expect($tokens[3]->type->name)->toBe('And');

    expect($tokens[4]->type->name)->toBe('LeftParen');

    expect($tokens[5]->type->name)->toBe('Field');
    expect($tokens[6]->type->name)->toBe('Equal');
    expect($tokens[7]->type->name)->toBe('String');

    expect($tokens[8]->type->name)->toBe('Or');

    expect($tokens[9]->type->name)->toBe('Field');
    expect($tokens[10]->type->name)->toBe('Equal');
    expect($tokens[11]->type->name)->toBe('String');

    expect($tokens[12]->type->name)->toBe('RightParen');
});

test('More nesting', function () {
    $lexer = new QueryLexer();
    $tokens = $lexer->tokens('(field = "test" & ((name.de = "test") | name.en = "test"))');

    expect($tokens[0]->type->name)->toBe('LeftParen');

    expect($tokens[1]->type->name)->toBe('Field');
    expect($tokens[2]->type->name)->toBe('Equal');
    expect($tokens[3]->type->name)->toBe('String');

    expect($tokens[4]->type->name)->toBe('And');

    expect($tokens[5]->type->name)->toBe('LeftParen');
    expect($tokens[6]->type->name)->toBe('LeftParen');

    expect($tokens[7]->type->name)->toBe('Field');
    expect($tokens[8]->type->name)->toBe('Equal');
    expect($tokens[9]->type->name)->toBe('String');
    expect($tokens[10]->type->name)->toBe('RightParen');

    expect($tokens[11]->type->name)->toBe('Or');

    expect($tokens[12]->type->name)->toBe('Field');
    expect($tokens[13]->type->name)->toBe('Equal');
    expect($tokens[14]->type->name)->toBe('String');

    expect($tokens[15]->type->name)->toBe('RightParen');
    expect($tokens[16]->type->name)->toBe('RightParen');
});

test('Token groups', function () {
    $lexer = new QueryLexer(['builtin1', 'builtin2', 'builtin3']);
    $tokens = $lexer->tokens(QUERY_ALL_ELEMENTS);

    expect($tokens[0]->group->name)->toBe('LeftParen');
    expect($tokens[1]->group->name)->toBe('Operand');
    expect($tokens[2]->group->name)->toBe('Operator');
    expect($tokens[3]->group->name)->toBe('Operand');
    expect($tokens[4]->group->name)->toBe('BooleanOperator');
    expect($tokens[5]->group->name)->toBe('Operand');
    expect($tokens[6]->group->name)->toBe('Operator');
    expect($tokens[7]->group->name)->toBe('Operand');
    expect($tokens[8]->group->name)->toBe('BooleanOperator');
    expect($tokens[9]->group->name)->toBe('Operand');
    expect($tokens[10]->group->name)->toBe('Operator');
    expect($tokens[11]->group->name)->toBe('Operand');
    expect($tokens[12]->group->name)->toBe('BooleanOperator');
    expect($tokens[13]->group->name)->toBe('Operand');
    expect($tokens[14]->group->name)->toBe('Operator');
    expect($tokens[15]->group->name)->toBe('Operand');
    expect($tokens[16]->group->name)->toBe('RightParen');
    expect($tokens[17]->group->name)->toBe('BooleanOperator');
    expect($tokens[18]->group->name)->toBe('LeftParen');
    expect($tokens[19]->group->name)->toBe('Operand');
    expect($tokens[20]->group->name)->toBe('Operator');
    expect($tokens[21]->group->name)->toBe('Operand');
    expect($tokens[22]->group->name)->toBe('BooleanOperator');
    expect($tokens[23]->group->name)->toBe('Operand');
    expect($tokens[24]->group->name)->toBe('Operator');
    expect($tokens[25]->group->name)->toBe('Operand');
    expect($tokens[26]->group->name)->toBe('BooleanOperator');
    expect($tokens[27]->group->name)->toBe('Operand');
    expect($tokens[28]->group->name)->toBe('Operator');
    expect($tokens[29]->group->name)->toBe('Operand');
    expect($tokens[30]->group->name)->toBe('BooleanOperator');
    expect($tokens[31]->group->name)->toBe('Operand');
    expect($tokens[32]->group->name)->toBe('Operator');
    expect($tokens[33]->group->name)->toBe('Operand');
    expect($tokens[34]->group->name)->toBe('BooleanOperator');
    expect($tokens[35]->group->name)->toBe('Operand');
    expect($tokens[36]->group->name)->toBe('Operator');
    expect($tokens[37]->group->name)->toBe('Operand');
    expect($tokens[38]->group->name)->toBe('BooleanOperator');
    expect($tokens[39]->group->name)->toBe('Operand');
    expect($tokens[40]->group->name)->toBe('Operator');
    expect($tokens[41]->group->name)->toBe('Operand');
    expect($tokens[42]->group->name)->toBe('RightParen');
});

test('Token types', function () {
    $lexer = new QueryLexer(['builtin1', 'builtin2', 'builtin3']);
    $tokens = $lexer->tokens(QUERY_ALL_ELEMENTS);

    expect($tokens[0]->type->name)->toBe('LeftParen');
    expect($tokens[1]->type->name)->toBe('Boolean');
    expect($tokens[2]->type->name)->toBe('Equal');
    expect($tokens[3]->type->name)->toBe('Field');
    expect($tokens[4]->type->name)->toBe('And');
    expect($tokens[5]->type->name)->toBe('Builtin');
    expect($tokens[6]->type->name)->toBe('Greater');
    expect($tokens[7]->type->name)->toBe('Keyword');
    expect($tokens[8]->type->name)->toBe('And');
    expect($tokens[9]->type->name)->toBe('Null');
    expect($tokens[10]->type->name)->toBe('GreaterEqual');
    expect($tokens[11]->type->name)->toBe('Number');
    expect($tokens[12]->type->name)->toBe('And');
    expect($tokens[13]->type->name)->toBe('Field');
    expect($tokens[14]->type->name)->toBe('Less');
    expect($tokens[15]->type->name)->toBe('String');
    expect($tokens[16]->type->name)->toBe('RightParen');
    expect($tokens[17]->type->name)->toBe('Or');
    expect($tokens[18]->type->name)->toBe('LeftParen');
    expect($tokens[19]->type->name)->toBe('Number');
    expect($tokens[20]->type->name)->toBe('LessEqual');
    expect($tokens[21]->type->name)->toBe('Builtin');
    expect($tokens[22]->type->name)->toBe('Or');
    expect($tokens[23]->type->name)->toBe('Field');
    expect($tokens[24]->type->name)->toBe('ILike');
    expect($tokens[25]->type->name)->toBe('String');
    expect($tokens[26]->type->name)->toBe('Or');
    expect($tokens[27]->type->name)->toBe('Builtin');
    expect($tokens[28]->type->name)->toBe('IUnlike');
    expect($tokens[29]->type->name)->toBe('String');
    expect($tokens[30]->type->name)->toBe('Or');
    expect($tokens[31]->type->name)->toBe('Path');
    expect($tokens[32]->type->name)->toBe('Unequal');
    expect($tokens[33]->type->name)->toBe('Number');
    expect($tokens[34]->type->name)->toBe('Or');
    expect($tokens[35]->type->name)->toBe('Path');
    expect($tokens[36]->type->name)->toBe('Unlike');
    expect($tokens[37]->type->name)->toBe('String');
    expect($tokens[38]->type->name)->toBe('And');
    expect($tokens[39]->type->name)->toBe('Field');
    expect($tokens[40]->type->name)->toBe('Like');
    expect($tokens[41]->type->name)->toBe('String');
    expect($tokens[42]->type->name)->toBe('RightParen');
});
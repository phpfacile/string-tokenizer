<?php
declare(strict_types=1);

namespace PHPFacile\Test;

use PHPUnit\Framework\TestCase;

use PHPFacile\StringTokenizer\Helper\StringTokenizerHelper;

final class StringTokenizerHelperTest extends TestCase
{

    public function testParseKeywords()
    {
        $str = 'begin [% keyword %] end';
        $expectedOutput = [
            'begin ',
            ['keyword'],
            ' end',
        ];
        $output = StringTokenizerHelper::parseKeywords($str, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'Parsing of ['.$str.'] failed.');

        $str = '[% keywordAtBegin %] end';
        $expectedOutput = [
            ['keywordAtBegin'],
            ' end',
        ];
        $output = StringTokenizerHelper::parseKeywords($str, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'Parsing of ['.$str.'] failed.');

        $str = 'begin [% keywordAtEnd %]';
        $expectedOutput = [
            'begin ',
            ['keywordAtEnd'],
        ];
        $output = StringTokenizerHelper::parseKeywords($str, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'Parsing of ['.$str.'] failed.');

        $str = 'begin [% keyword %][% keywordStuckToPreviousOne %] end';
        $expectedOutput = [
            'begin ',
            ['keyword'],
            ['keywordStuckToPreviousOne'],
            ' end'
        ];
        $output = StringTokenizerHelper::parseKeywords($str, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'Parsing of ['.$str.'] failed.');

        $str = 'begin [% unTerminatedKeyword';
        $expectedOutput = [
            'begin ',
            ['unTerminatedKeyword'],
        ];
        $output = StringTokenizerHelper::parseKeywords($str, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'Parsing of ['.$str.'] failed.');
    }

    public function testGetKeywordSubstitutedString()
    {
        $str = 'begin [% myKeyword %] end';
        $keywordValues = [
            'myKeyword' => 'middle'
        ];
        $expectedOutput = 'begin middle end';
        $output = StringTokenizerHelper::getKeywordSubstitutedString($str, $keywordValues, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'keyword substitution for ['.$str.'] with values ['.var_export($keywordValues, true).'] failed.');

        $keywordValues = [
            'anyOtherKeyword' => 'middle'
        ];
        $expectedOutput = 'begin [% myKeyword %] end';
        $output = StringTokenizerHelper::getKeywordSubstitutedString($str, $keywordValues, '[%', '%]');
        $this->assertEquals($expectedOutput, $output, 'keyword substitution for ['.$str.'] with values ['.var_export($keywordValues, true).'] failed.');

        $expectedOutput = 'begin  end';
        $output = StringTokenizerHelper::getKeywordSubstitutedString($str, $keywordValues, '[%', '%]', StringTokenizerHelper::UNMATCHED_KEYWORD_STRATEGY_EMPTY);
        $this->assertEquals($expectedOutput, $output, 'keyword substitution for ['.$str.'] with values ['.var_export($keywordValues, true).'] failed.');
    }
}
<?php

namespace PHPFacile\StringTokenizer\Helper;

class StringTokenizerHelper
{

    const KEYWORD_IDENTIFIER_STRATEGY_ARRAY = 'array';
    const UNMATCHED_KEYWORD_STRATEGY_LET_KEYWORD = 'keyword';
    const UNMATCHED_KEYWORD_STRATEGY_EMPTY = 'empty';

    /**
     * Return an array containing pure text and keywords extracted from a string.
     * Keywords have to be delimited by a begin and an end delimiter (ex: "[% keyword %]").
     * 
     * So as to ease distinction between pure text and keywords in the returned array, pure text is return as a string whereas
     * by default, keywords are returned as an array with a single string value which is the keyword
     * (without delimiters).
     * 
     * By default, keywords between delimiters are trimed.
     * By default, if we cannot find a keyword end delimiter (matching a previous begin delimiter) before the end of the string, 
     *     all the remaining part of the string is regarded as a keyword
     * 
     * Ex: "begin [% keyword %] end" would return ['begin ', ['keyword'], ' end']
     * 
     * @param string $str                       String to parse
     * @param string $keywordDelimiterBegin     Delimiter used before the keyword (ex: '[%')
     * @param string $keywordDelimiterEnd       Delimiter used after the keyword (ex: '%]')
     * @param string $keywordIdentifierStrategy Strategy used to identify the keywords in the returned array (default: use an array containing the keyword)
     * @param bool   $trimKeywords              Whether or not to remove white spaces at begin and end of the keyword (without the delimiters)
     * 
     * @return array
     */
    function parseKeywords(string $str, string $keywordDelimiterBegin, string $keywordDelimiterEnd, string $keywordIdentifierStrategy = self::KEYWORD_IDENTIFIER_STRATEGY_ARRAY, bool $trimKeywords = true): array
    {
        $currentTokenType = 'string';
        $delimiter = $keywordDelimiterBegin;
        $tokens = [];

        while (false !== ($pos = mb_strpos($str, $delimiter))) {
            if (0 !== $pos) {
                $token = mb_substr($str, 0, $pos);
                self::completeArrayWithTokenForKeywordParser($tokens, $token, $currentTokenType, $keywordIdentifierStrategy, $trimKeywords);
            }

            $str = mb_substr($str, $pos + mb_strlen($delimiter));

            if ('string' === $currentTokenType) {
                $currentTokenType = 'keyword';
                $delimiter = $keywordDelimiterEnd;
            } else {
                $currentTokenType = 'string';
                $delimiter = $keywordDelimiterBegin;
            }
        }

        if (mb_strlen($str) > 0) {
            self::completeArrayWithTokenForKeywordParser($tokens, $str, $currentTokenType, $keywordIdentifierStrategy, $trimKeywords);
        }

        return $tokens;
    }

    protected function completeArrayWithTokenForKeywordParser(array &$tokens, string $token, string $tokenType, string $keywordIdentifierStrategy, bool $trimKeywords)
    {
        if ('string' === $tokenType) {
            $tokens[] = $token;
        } else {
            if ($trimKeywords) {
                $token = trim($token);
            }

            switch ($keywordIdentifierStrategy) {
                case self::KEYWORD_IDENTIFIER_STRATEGY_ARRAY:
                    $tokens[] = [$token];
                    break;
                default:
                    throw new \Exception('Unsupported keyword identifier strategy ['.$keywordIdentifierStrategy.']');
            }
        }
    }

    /**
     * Return a string where all the keywords have been replaced by there value (if a value is provided)
     * Keywords have to be delimited by a begin and an end delimiter (ex: "[% keyword %]").
     * 
     * Ex: "begin [% keyword %] end" + ['keyword' => 'middle'] would return 'begin middle end'
     * 
     * @param string $str                      String to parse
     * @param array  $keywordValues            Associative array keyword => keywordValue
     * @param string $keywordDelimiterBegin    Delimiter used before the keyword (ex: '[%')
     * @param string $keywordDelimiterEnd      Delimiter used after the keyword (ex: '%]')
     * @param string $unmatchedKeywordStrategy What to do if the keyword value cannot be found in the provided $keywordValues array. By default, let the keyword in the returned string.
     * 
     * @return string
     */
    public function getKeywordSubstitutedString(string $str, array $keywordValues, string $keywordDelimiterBegin, string $keywordDelimiterEnd, string $unmatchedKeywordStrategy = self::UNMATCHED_KEYWORD_STRATEGY_LET_KEYWORD): string
    {
        $tokens = self::parseKeywords($str, $keywordDelimiterBegin, $keywordDelimiterEnd, self::KEYWORD_IDENTIFIER_STRATEGY_ARRAY, true);

        $keywordSubstitutedStr = '';
        foreach ($tokens as $token) {
            if (is_string($token)) {
                // Not a keyword
                $keywordSubstitutedStr .= $token;
            } else if (is_array($token) && (1 === count($token)) && (array_key_exists(0, $token))) {
                // it's a keyword
                $keyword = $token[0];
                if (array_key_exists($keyword, $keywordValues)) {
                    $keywordSubstitutedStr .= $keywordValues[$keyword];
                } else {
                    switch ($unmatchedKeywordStrategy) {
                        case self::UNMATCHED_KEYWORD_STRATEGY_LET_KEYWORD:
                            $keywordSubstitutedStr .= $keywordDelimiterBegin.' '.$keyword.' '.$keywordDelimiterEnd;
                            break;
                        case self::UNMATCHED_KEYWORD_STRATEGY_EMPTY:
                            break;
                        default:
                            throw new \Exception('Unsupported unmatched keyword strategy ['.$keywordIdentifierStrategy.']');
                    }
                }
            } else {
                throw new \Exception('Unexpected token type in parsed string');
            }
        }
    
        return $keywordSubstitutedStr;
    }
}
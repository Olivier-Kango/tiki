<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Standards\TikiIgnore\Sniffs\Properties;

require_once __DIR__ . DIRECTORY_SEPARATOR . '../../Helpers/IgnoreListTrait.php';

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Tiki\Standards\TikiIgnore\Helpers\IgnoreListTrait;

class IgnoreConstantVisibilitySniff implements Sniff
{
    use IgnoreListTrait;

    protected const SNIFF_VISIBILITY_NOT_FOUND = 'PSR12.Properties.ConstantVisibility.NotFound';

    public function __construct()
    {
        $this->loadIgnoreList([self::SNIFF_VISIBILITY_NOT_FOUND]);
    }

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_CONST];
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Make sure this is a class constant.
        if ($phpcsFile->hasCondition($stackPtr, Tokens::$ooScopeTokens) === false) {
            return;
        }

        $ignore   = Tokens::$emptyTokens;
        $ignore[] = T_FINAL;

        $prev = $phpcsFile->findPrevious($ignore, ($stackPtr - 1), null, true);
        if (isset(Tokens::$scopeModifiers[$tokens[$prev]['code']]) === true) {
            return;
        }

        $constant = $tokens[$phpcsFile->findNext(T_STRING, $stackPtr)]['content'];
        if ($this->inIgnoreList(self::SNIFF_VISIBILITY_NOT_FOUND, $phpcsFile->path, $constant)) {
            $this->ignoreToken(self::SNIFF_VISIBILITY_NOT_FOUND, $phpcsFile, $tokens, $stackPtr);
        }
    }
}

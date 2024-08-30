<?php

namespace SmartyTiki\Compile\Modifier;

use Smarty\Exception;
use Smarty\Compile\Modifier\Base;

/**
 * Smarty escape modifier plugin
 * Type:     modifier
 * Name:     escape
 * Purpose:  escape string for output
 *
 * @author Rodney Rehm
 */

class EscapeModifierCompiler extends Base {

	public function compile($params, \Smarty\Compiler\Template $compiler) {
		// pass through to regular plugin fallback
		return '$_smarty_tpl->getSmarty()->getModifierCallback(\'escape\')(' . join(', ', $params) . ')';
	}
}

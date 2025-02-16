<?php

/**
 * A class to render Diffs in different formats.
 *
 * This class renders the diff in classic diff format. It is intended that
 * this class be customized via inheritance, to obtain fancier outputs.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer.php,v 1.5 2004/10/13 09:30:20 jan Exp $
 *
 * @package Text_Diff
 */
class Text_Diff_Renderer
{
    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses may want to
     * set this to other values.
     */
    protected $leading_context_lines = 0;

    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses may want to
     * set this to other values.
     */
    protected $trailing_context_lines = 0;

    /**
     * Constructor.
     */
    public function __construct($params = [])
    {
        foreach ($params as $param => $value) {
            $v = '_' . $param;
            if (isset($this->$v)) {
                $this->$v = $value;
            }
        }
    }

    /**
     * Renders a diff.
     *
     * @param Text_Diff $diff  A Text_Diff object.
     *
     * @return string  The formatted output.
     */
    public function render($diff)
    {
        $xi = $yi = 1;
        $block = false;
        $context = [];

        $nlead = $this->leading_context_lines;
        $ntrail = $this->trailing_context_lines;

        $this->_startDiff();

        foreach ($diff->getDiff() as $edit) {
            if (is_a($edit, 'Text_Diff_Op_copy')) {
                if (is_array($block)) {
                    if (count($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new Text_Diff_Op_copy($context);
                        }
                        $this->_block(
                            $x0,
                            $ntrail + $xi - $x0,
                            $y0,
                            $ntrail + $yi - $y0,
                            $block
                        );
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (! is_array($block)) {
                    $context = array_slice($context, count($context) - $nlead);
                    $x0 = $xi - count($context);
                    $y0 = $yi - count($context);
                    $block = [];
                    if ($context) {
                        $block[] = new Text_Diff_Op_copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += count($edit->orig);
            }
            if ($edit->final) {
                $yi += count($edit->final);
            }
        }

        if (is_array($block)) {
            $this->_block(
                $x0,
                $xi - $x0,
                $y0,
                $yi - $y0,
                $block
            );
        }

        return $this->_endDiff();
    }

    protected function _block($xbeg, $xlen, $ybeg, $ylen, &$edits)
    {
        $this->_startBlock($this->_blockHeader($xbeg, $xlen, $ybeg, $ylen));
        foreach ($edits as $edit) {
            switch (strtolower(get_class($edit))) {
                case 'text_diff_op_copy':
                    $this->_context($edit->orig);
                    break;

                case 'text_diff_op_add':
                    $this->_added($edit->final);
                    break;

                case 'text_diff_op_delete':
                    $this->_deleted($edit->orig);
                    break;

                case 'text_diff_op_change':
                    $this->_changed($edit->orig, $edit->final);
                    break;

                default:
                    trigger_error("Unknown edit type", E_USER_WARNING);
            }

            $this->_endBlock();
        }
    }

    protected function _startDiff()
    {
        ob_start();
    }

    protected function _endDiff()
    {
        $val = ob_get_contents();
        ob_end_clean();
        return $val;
    }

    protected function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen > 1) {
            $xbeg .= ',' . ($xbeg + $xlen - 1);
        }
        if ($ylen > 1) {
            $ybeg .= ',' . ($ybeg + $ylen - 1);
        }

        return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
    }

    protected function _startBlock($header)
    {
        // TODO: What's this output for? It breaks XML pages
        // echo $header . "\n";
    }

    protected function _endBlock()
    {
    }

    protected function _lines($lines, $prefix = '', $suffix = '', $type = '')
    {
        foreach ($lines as $line) {
            echo "$prefix$line$suffix\n";
        }
    }

    protected function _context($lines)
    {
        $this->_lines($lines, ' ');
    }

    protected function _added($lines)
    {
        $this->_lines($lines, '>');
    }

    protected function _deleted($lines)
    {
        $this->_lines($lines, '<');
    }

    protected function _changed($orig, $final)
    {
        $this->_deleted($orig);
        echo "---\n";
        $this->_added($final);
    }
}

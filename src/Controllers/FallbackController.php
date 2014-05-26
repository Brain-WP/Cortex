<?php namespace Brain\Cortex\Controllers;

use Brain\Cortex\RequestableInterface;

/**
 * FallbackController runs, if added to router, when no registered route matched
 * or when there are no routes registereed.
 *
 * It's possible to set a minum/maximum/exact number of url pieces for pieces to check against
 * before run.
 *
 * @author Giuseppe Mazzapica
 * @package Brain\Cortex
 */
abstract class FallbackController implements ControllerInterface, RequestableInterface {

    use \Brain\Cortex\Requestable;

    /**
     * Request object. Injected by router using FallbackController::setRequest()
     * @var \Brain\Request
     */
    protected $request;

    /**
     * If > 0 FallbackController runs only if url pieces are > than this number.
     * @var integer
     */
    protected $min_pieces = 0;

    /**
     * When $min_pieces is > 0, makes the check for pieces exact, i.e. use == instead of >.
     * @var boolean
     */
    protected $exact_count = FALSE;

    /**
     * Callback the result of which determines whether or not to launch the fallback
     * @var callable
     */
    protected $condition;

    /**
     * Retrieve the number of pieces to check against the url pieces to allow the controller to run.
     *
     * @access public
     * @return int|void
     */
    public function getMinPieces() {
        return $this->min_pieces;
    }

    /**
     * Set the number of pieces to check against the url pieces to allow the controller to run.
     *
     * @param int $min_pieces the number of pieces. Positive int means minimum, negative maximum
     * @access public
     * @return \Brain\Cortex\Controllers\FallbackController Self
     */
    public function setMinPieces( $min_pieces ) {
        if ( ! is_numeric( $min_pieces ) ) throw new \InvalidArgumentException;
        $this->min_pieces = (int) $min_pieces;
        return $this;
    }

    /**
     * Retrieve callback the result of which determines whether or not to launch the fallback
     *
     * @access public
     * @return callable|void
     */
    public function getCondition() {
        return $this->condition;
    }

    /**
     * Set a callback that will be performed before the fallback runs,
     * if it reurns false, the fallback is ignored
     *
     * @param callable $condition
     * @access public
     * @return \Brain\Cortex\Controllers\FallbackController Self
     */
    public function setCondition( $condition ) {
        if ( ! is_callable( $condition ) ) throw new \InvalidArgumentException;
        $this->condition = $condition;
        return $this;
    }

    /**
     * Used as setter and getter for the exact_count property. When exact_count is TRUE
     * (by default is FALSE) and a num of min num pieces is setted, the check is done checking
     * the exact number of url pieces instead of the minimum one
     *
     * @param bool $exact True to allow exact match. Passing nothing method act as a getter
     * @access public
     * @return bool|void
     */
    public function isExact( $exact = NULL ) {
        if ( is_null( $exact ) ) return (bool) $this->exact_count;
        $this->exact_count = (bool) $exact;
        return $this;
    }

    /**
     * Called by router to allow run fallback (if returns TRUE) or not (if returns anything else).
     * It return TRUE when
     *  - min pieces is 0 (default)
     *  - min pieces is a positive int, $this->isExact() is false and url pieces is >= of min pieces
     *  - min pieces is a negative int, $this->isExact() is false and url pieces is <= of min pieces
     *  - min pieces is a positive int, $this->isExact() is true and url pieces is == of min pieces
     *
     * @access public
     * @return bool|void
     * @see Cortex\Controllers\FallbackController::getMinPieces()
     * @see Cortex\Controllers\FallbackController::isExact()
     */
    public function should() {
        if ( is_callable( $this->getCondition() ) ) {
            $test = call_user_func( $this->getCondition(), $this->getRequest() );
            if ( empty( $test ) ) return FALSE;
        }
        $min = $this->getMinPieces();
        if ( ! is_numeric( $min ) ) throw new \DomainException;
        $m = (int) $min;
        if ( $m === 0 ) return TRUE;
        $p = count( $this->getRequest()->pathPieces() );
        if ( $this->isExact() && $m < 0 ) return FALSE;
        return ! $this->isExact() ? ( $m > 0 && $p >= $m ) || ( $m + $p <= 0 ) : ( $p === $m );
    }

}
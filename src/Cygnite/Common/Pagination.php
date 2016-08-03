<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common;

use Closure;
use Cygnite\Proxy\StaticResolver;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\UrlManager\Url;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

/**
 * Class Pagination
 *
 * <code>
 * $paginator = new \Cygnite\Common\Pagination;
 * $paginator->setTotalNumberOfPage();
 * $paginator->setPerPage(5);
 * $paginator->createLinks();
 * </code>
 *
 * @package Cygnite\Common
 */
class Pagination
{
    protected $perPage = '15';

    public $model;

    private $numCount = 'number_of_records';

    public $pageNumber;

    private $adjacent = 3;

    private $lastPage;

    private $lastPageMinusOne;

    private $previous;

    private $next;

    private $currentPageUrl;

    private $paginationOffset;

    private $paginationLinks;

    protected $totalNumOfPage;

    /**
     * Pagination constructor
     *
     * @param $model
     */
    public function __construct($model = null)
    {
        if (!is_null($model) && is_object($model)) {
            $this->model = $model;
        }

        $this->setCurrentPageUrl();
    }

    /**
     * @param array    $args
     * @param callable $callback
     * @return Pagination
     */
    public static function make($args = null, Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new static($args));
        }

        return new static($args);
    }

    /**
     * Set per page record
     *
     * @param null $number
     * @return $this
     */
    public function setPerPage($number = null)
    {
        if (is_null($number)) {
            if (property_exists($this->model, 'perPage')) {
                $this->perPage = $this->model->perPage;
                return $this;
            }
        }

        $this->perPage = $number;

        return $this;
    }

    /**
     * Set total number of pages
     *
     * @param null $number
     * @return $this
     */
    public function setTotalNumberOfPage($number = null)
    {
        if (is_null($number)) {
            $numRecords = $this->model->select($this->count()." AS ".$this->numCount)->findOne();
            $this->totalNumOfPage = $numRecords[0]->{$this->numCount};

            return $this;
        }

        $this->totalNumOfPage = $number;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalNumberOfPages()
    {
        return $this->totalNumOfPage;
    }

    /**
     * Make the count row
     *
     * @param string $count
     * @return string
     */
    private function count($count = '*')
    {
        $func = strtoupper(__FUNCTION__);

        return (string) $func."($count)";
    }

    /**
     * Render the pagination links
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->render();
    }

    /**
     * Calculate the pagination links
     *
     */
    public function calculate()
    {
        $this->calculatePageLimitAndOffset();
        $pageNumber = $this->getPageNumber();
        $offset = $this->getPaginationOffset();


        /* Setup page vars for display. */
        if ($pageNumber == 0) {
            $pageNumber = 1;//if no page var is given, default to 1.
        }
        $this->previous = $pageNumber - 1;
        $this->next = $pageNumber + 1;
        //last page is = total pages / items per page, rounded up.
        $this->lastPage = ceil($this->getTotalNumberOfPages()/$this->perPage);

        $this->lastPageMinusOne = $this->lastPage - 1;  //last page minus 1
        $this->create();
    }

    /**
     * Calculate page limit and offset
     */
    private function calculatePageLimitAndOffset()
    {
        $pageUri = '';
        $pageUri = Url::segment(3);
        $pageNumber =  ($pageUri !== '') ? $pageUri : 0;

        if ($pageNumber) {
            //calculate starting point of pagination
            $start = ($this->getPageNumber() - 1) * $this->perPage;
        } else {
            $start = 0; //set start to 0 by default.
        }

        $this->setPageNumber($pageNumber);
        $this->setPaginationOffset($start);
    }

    /**
     * Set current page url
     */
    private function setCurrentPageUrl()
    {
        $controller = Url::segment(1);
        $method = '';
        $method = (Url::segment(2) == '') ? 'index' : Url::segment(2);

        $this->currentPageUrl = Url::getBase().$controller.'/'.$method;
    }

    /**
     * Set page number
     *
     * @param $number
     */
    public function setPageNumber($number)
    {
        $this->pageNumber = intval($number);
    }

    /**
     * Get page number
     *
     * @return null
     */
    public function getPageNumber()
    {
        return (isset($this->pageNumber)) ? $this->pageNumber : null;
    }

    /**
     * Set pagination offset
     *
     * @param $offset
     */
    public function setPaginationOffset($offset)
    {
        $this->paginationOffset = intval($offset);
    }

    /**
     * Get Pagination offset
     *
     * @return null
     */
    public function getPaginationOffset()
    {
        return (isset($this->paginationOffset)) ? $this->paginationOffset : null;
    }

    /**
     * Create pagination links
     *
     * @return $this
     */
    public function createLinks()
    {
        $this->getTotalNumberOfPages();
        $this->calculate();

        return $this;
    }

    /**
     * Create pagination links
     *
     */
    public function create()
    {
        $content = "";
        $pageNumber = $this->getPageNumber();

        if ($pageNumber === 0) {
            $pageNumber = 1;
        }

        if ($this->lastPage > 1) {
            $content .= "<div class='pagination'>";

            $content .= $this->renderPreviousLink($pageNumber);

            //not enough pages to bother breaking it up
            if ($this->lastPage < 7 + ($this->adjacent * 2)) {
                for ($counter = 1; $counter <= $this->lastPage; $counter++) {
                    $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                }
            } elseif ($this->lastPage > 5 + ($this->adjacent * 2)) {
                //close to beginning; only hide later pages
                if ($pageNumber < 1 + ($this->adjacent * 2)) {
                    for ($counter = 1; $counter < 4 + ($this->adjacent * 2); $counter++) {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }
                    $content.= "...";
                    $content.= $this->createSecondLink();
                } elseif (
                    $this->lastPage - ($this->adjacent * 2) > $pageNumber
                    && $pageNumber > ($this->adjacent * 2)
                ) {
                    $content.= $this->createPrimaryLink();
                    $content.= "...";

                    for (
                        $counter = $pageNumber - $this->adjacent;
                        $counter <= $pageNumber + $this->adjacent;
                        $counter++
                    ) {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }

                    $content.= "...";
                    $content.= $this->createPrimaryLink();
                    $content.= $this->createSecondLink();
                } else {
                    //close to end; only hide early pages

                    $content.= $this->createPrimaryLink();

                    $content.= "...";
                    for (
                        $counter = $this->lastPage - (2 + ($this->adjacent * 2));
                        $counter <= $this->lastPage;
                        $counter++
                    ) {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }
                }
            }

            $content.= $this->renderNextLink($counter, $pageNumber);
        }

        $this->paginationLinks = $content;
    }

    /**
     * @param        $pageNumber
     * @param        $counter
     * @param string $content
     * @return string
     */
    private function createCurrentActiveLink($pageNumber, $counter, $content = '')
    {
        if ($counter == $pageNumber) {
            $content.= "<span class='current'>$counter</span>";
        } else {
            $content.= "<a href='".$this->currentPageUrl."/".$counter."'>$counter</a>";
        }

        return $content;
    }

    private function createPrimaryLink()
    {
        $content = '';
        $content.= "<a href='".$this->currentPageUrl."/1'>1</a>";
        $content.= "<a href='".$this->currentPageUrl."/2'>2</a>";

        return $content;
    }

    /**
     * Create Second link
     *
     * @return string
     */
    private function createSecondLink()
    {
        $content = '';
        $content.=
            "<a href='".$this->currentPageUrl."/".$this->lastPageMinusOne."'>$this->lastPageMinusOne</a>";
        $content.=
            "<a href='".$this->currentPageUrl."/".$this->lastPage."'>$this->lastPage</a>";

        return $content;
    }

    /**
     * @param        $pageNumber
     * @param string $content
     * @return string
     */
    public function renderPreviousLink($pageNumber, $content = '')
    {
        //generate previous link
        if ($pageNumber > 1) {
            $content.= "<a href='".$this->currentPageUrl."/".$this->previous."'> previous</a>";
        } else {
            $content.= "<span class='disabled'> previous</span>";
        }

        return $content;
    }

    /**
     * @param        $counter
     * @param        $pageNumber
     * @param string $content
     * @return string
     */
    public function renderNextLink($counter, $pageNumber, $content = '')
    {
        //create next link
        if ($pageNumber < $counter - 1) {
            $content.= "<a href='".$this->currentPageUrl.'/'.$this->next."'>next </a>";
        } else {
            $content.= "<span class=\"disabled\">next </span>";
        }
        $content.= "</div>\n";

        return $content;
    }

    /**
     * Render pagination links
     *
     * @return mixed
     */
    protected function render()
    {
        return $this->paginationLinks;
    }
}

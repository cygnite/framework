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
/*
 * Pagination.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * <code>
 * $paginator = new \Cygnite\Common\Pagination;
 * $paginator->getItems();
 * $paginator->getTotalNumOfItems();
 * $paginator->setPerPage(5);
 * $paginator->createLinks();
 * </code>
 */

class Pagination
{

    private $perPage = '15';

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

    /**
     * @param $model
     */
    private function __construct($model)
    {
        $this->model = $model;
        $this->setCurrentPageUrl();
    }

    /**
     * @param array    $args
     * @param callable $callback
     * @return Pagination
     */
    public static function instance($args = array(), Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new self($args));
        }

        return new self($args);
    }

    /**
     * @param null $number
     */
    public function setPerPage($number = null)
    {
        if (is_null($number)) {
            if (property_exists($this->model,'perPage')) {
                $this->perPage = $this->model->perPage;
            }
        } else {
            $this->perPage = $number;
        }
    }

    /**
     * @return mixed
     */
    public function getTotalNumberOfPages()
    {
       $numRecords = null;

       $modelClass = Inflector::instance()->getClassNameFromNamespace(get_class($this->model));
       $table = Inflector::instance()->tabilize($modelClass);

       $numRecords = $this->model
                          ->query("SELECT ".$this->count()." as ".$this->numCount." FROM `".$table."`")
                          ->getAll();

       return $numRecords[0]->{$this->numCount};
    }

    /**
     * @param string $count
     * @return string
     */
    private function count($count = '*')
    {
        $func = strtoupper(__FUNCTION__);
        return (string) $func."($count)";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->render();
    }


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
        $this->lastPage = ceil($this->getTotalNumberOfPages()/$this->model->perPage);

        $this->lastPageMinusOne = $this->lastPage - 1;	//last page minus 1
        $this->create();
    }


    private function calculatePageLimitAndOffset()
    {
        $pageUri = '';
        $pageUri = Url::segment(3);

        $pageNumber =  ($pageUri !== '')
            ? $pageUri
            : 0;

        if ($pageNumber) {
            //calculate starting point of pagination
            $start = ($this->getPageNumber() - 1) * $this->model->perPage;
        } else {
            $start = 0; //set start to 0 by default.
        }

        $this->setPageNumber($pageNumber);
        $this->setPaginationOffset($start);
    }

    private function setCurrentPageUrl()
    {
        $controller = Url::segment(1);

        $method = '';
        $method = Url::segment(2);

        if (Url::segment(2) == '') {
            $method = 'index';
        }

        $this->currentPageUrl = Url::getBase().$controller.'/'.$method;
    }

    public function setPageNumber($number)
    {
        $this->pageNumber = intval($number);
    }

    public function getPageNumber()
    {
        return (isset($this->pageNumber)) ? $this->pageNumber : null;
    }

    public function setPaginationOffset($offset)
    {
        $this->paginationOffset = intval($offset);
    }

    public function getPaginationOffset()
    {
        return (isset($this->paginationOffset)) ? $this->paginationOffset : null;
    }

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

        if($this->lastPage > 1)
        {
            $content .= "<div class=\"pagination\">";

            $content .= $this->renderPreviousLink($pageNumber);

            //not enough pages to bother breaking it up
            if ($this->lastPage < 7 + ($this->adjacent * 2)) {

                for ($counter = 1; $counter <= $this->lastPage; $counter++) {
                    $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                }

            } elseif($this->lastPage > 5 + ($this->adjacent * 2)) {
                //close to beginning; only hide later pages
                if($pageNumber < 1 + ($this->adjacent * 2)) {

                    for ($counter = 1; $counter < 4 + ($this->adjacent * 2); $counter++) {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }
                    $content.= "...";
                    $content.= $this->createSecondLink();

                } elseif(
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

                } else {//close to end; only hide early pages

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

    private function createCurrentActiveLink($pageNumber, $counter, $content = '')
    {
        if ($counter == $pageNumber) {
            $content.= "<span class=\"current\">$counter</span>";
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

    private function createSecondLink()
    {
        $content = '';
        $content.=
            "<a href='".$this->currentPageUrl."/".$this->lastPageMinusOne."'>$this->lastPageMinusOne</a>";
        $content.=
            "<a href='".$this->currentPageUrl."/".$this->lastPage."'>$this->lastPage</a>";

        return $content;
    }

    public function renderPreviousLink($pageNumber, $content = '')
    {
        //generate previous link
        if ($pageNumber > 1) {
            $content.= "<a href='".$this->currentPageUrl."/".$this->previous."'> previous</a>";
        } else {
            $content.= "<span class=\"disabled\"> previous</span>";
        }

        return $content;

    }

    public function renderNextLink($counter, $pageNumber, $content = '')
    {
        //create next link
        if ($pageNumber < $counter - 1)
            $content.= "<a href='".$this->currentPageUrl.'/'.$this->next."'>next </a>";
        else
            $content.= "<span class=\"disabled\">next </span>";
        $content.= "</div>\n";

        return $content;

    }

    private function render()
    {
        return $this->paginationLinks;
    }
}

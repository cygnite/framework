<?php
namespace Cygnite\Libraries;

use Closure;
use Cygnite\Helpers\Url;


if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/*
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package           :  Packages
 * @Sub Packages      :  Library
 * @Filename          :  Pagination
 * @Description       :  This library used to handle all http requests
 * @Author            :  Cygnite Dev Team
 * @Copyright         :  Copyright (c) 2013 - 2014,
 * @Link	          :  http://www.cygniteframework.com
 * @Since	          :  Version 1.0
 * @FileSource
 *
 *
 */

/*
$paginator = new Pagination;
$paginator->getItems();
$paginator->getTotalNumOfItems();
$paginator->setPerPage(5);
$paginator->createLinks();
*/

class Pagination
{

    private $perPage = '15';

    public $model;

    private $numCount = 'number_of_records';

    public $pageNumber;

    private $adjacents = 3;

    private $lastPage;

    private $lastPageMinusOne;

    private $previous;

    private $next;

    private $currentPageUrl;

    private function __construct($model)
    {
        $this->model = $model;
        $this->setCurrentPageUrl();
    }

    /*
    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return call_user_func_array(array(new self($arguments), 'get'.ucfirst($method)), $arguments);
        } elseif ($method == 'instance' && $arguments[0] instanceof Closure) {
            return call_user_func_array(array(new self($arguments), 'get'.ucfirst($method)), $arguments);
      }
    }
    */

    public static function instance($args = array(), Closure $callback = null)
    {
        if ($callback instanceof Closure) {
            return $callback(new self($args));
        }

        return new self($args);
    }

    public function setPerPage($number = null)
    {
        echo "per page";
        if (is_null($number)) {
            if (property_exists($this->model,'perPage')) {
                $this->perPage = $this->model->perPage;
            }
        } else {
            $this->perPage = $number;
        }
    }

    public function getTotalNumberOfPages()
    {
       $numRecords = null;

       $numRecords = $this->model
                          ->select($this->count()." as ".$this->numCount)
                          ->findAll();

       return $numRecords[0]->{$this->numCount};
    }

    private function count($count = '*')
    {
        $func = strtoupper(__FUNCTION__);
        return (string) $func."($count)";
    }

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
        $this->previous = $pageNumber - 1;							//previous page is page - 1
        $this->next = $pageNumber + 1;							//next page is page + 1
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

        /*
        echo $pageNumber;
        echo "<br>";
        echo $start;
        */

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

            //pages
            if ($this->lastPage < 7 + ($this->adjacents * 2))	//not enough pages to bother breaking it up
            {
                for ($counter = 1; $counter <= $this->lastPage; $counter++)
                {
                    $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                }
            }
            elseif($this->lastPage > 5 + ($this->adjacents * 2))	//enough pages to hide some
            {
                //close to beginning; only hide later pages
                if($pageNumber < 1 + ($this->adjacents * 2))
                {
                    for ($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++)
                    {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }
                    $content.= "...";
                    $content.= $this->createSecondLink();
                }
                //in middle; hide some front and some back
                elseif($this->lastPage - ($this->adjacents * 2) > $pageNumber && $pageNumber > ($this->adjacents * 2))
                {
                    $content.= $this->createPrimaryLink();
                    $content.= "...";

                    for ($counter = $pageNumber - $this->adjacents; $counter <= $pageNumber + $this->adjacents; $counter++)
                    {
                        $content.= $this->createCurrentActiveLink($pageNumber, $counter);
                    }

                    $content.= "...";
                    $content.= $this->createPrimaryLink();
                    $content.= $this->createSecondLink();

                }
                //close to end; only hide early pages
                else
                {
                    $content.= $this->createPrimaryLink();

                    $content.= "...";
                    for ($counter = $this->lastPage - (2 + ($this->adjacents * 2)); $counter <= $this->lastPage; $counter++)
                    {
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
        //draw next link
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

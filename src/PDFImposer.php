<?php

namespace NBDImposer;

use TCPDI;

class PDFImposer
{
    public $grid;
    private $pdf;
    private $document = array('width' => 0, 'height' => 0);
    private $pageRotation = array('Rotation' => 0);
    private $sourcePageNumber = 1;
    private $sourcePage;
    private $template;
    private $pageCount = 0;
    private $mode;
    private $firstPageRotation = 0;
    
    
    function __construct($width, $height, $source_file, $rows, $columns, $spacing, $options = array())
    {
        $this->document = array(
            'width' => $width,
            'height' => $height,
            'format' => array($width, $height),
            'orientation' => $width > $height ? 'L' : 'P',
            'rows' => $columns,
            'columns' => $rows,
            'spacing' => $spacing
        );
        
        $this->pdf = new TCPDI($this->document['orientation'], 'mm', $this->document['format'], true, 'UTF-8', false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pageCount = $this->pdf->setSourceFile($source_file);

        if(!empty($options) && isset($options['first_page_rotation'])) {
            $this->firstPageRotation = (int) $options['first_page_rotation'];
        }

        if(!empty($options) && isset($options['mode'])) {
            $mode = (int) $options['mode'];
            if($mode >= 1 && $mode <= 4) {
                $this->mode = $mode;
            }
        } else {
            if ($this->pageCount == 1) {
                $this->mode = PDFImposerMode::FRONT;
            } elseif ($this->pageCount == 2) {
                $this->mode = PDFImposerMode::FRONT_BACK;
            } elseif ($this->pageCount > 2) {
                $this->mode = PDFImposerMode::MULTIPAGE;
            }
        }

    }
    
    //Output document
    
    public function output($file, $mode = 'I')
    {
        $this->pdf->Output($file, $mode);
    }
    
    public function getMode()
    {
        return $this->mode;
    }
    
    
    //Grid
    
    public function impose($scale, $order, $rotation)
    {
        switch ($this->mode) {
            case PDFImposerMode::FRONT:
                $this->imposeFront($scale, $order[0], $rotation[0]);
                break;
            case PDFImposerMode::FRONT_BACK:
                $this->imposeFrontBack($scale, $order, $rotation);
                break;
            case PDFImposerMode::MULTIPAGE:
                $this->imposeMultipage($scale, $order, $rotation);
                break;
            case PDFImposerMode::MULTIPAGE_NOT_PERSONALIZED:
                $this->imposeMultipageNotPersonalized($scale, $order, $rotation);
                break;
        }
    }
    
    public function imposeFront($scale, $order, $rotation)
    {
        $this->importSourcePageAsTemplate(1, $scale);
        $this->addOutputPage($rotation);
        $this->renderGrid($order);
    }
    
    
    //Source
    
    public function importSourcePageAsTemplate($page_nr, $scale = 1)
    {
        $this->sourcePageNumber = $page_nr;
        $template = $this->pdf->importPage($this->sourcePageNumber);
        $size = $this->pdf->getTemplateSize($template);
        $this->sourcePage = array(
            'scale' => $scale,
            'width' => $size['w'],
            'height' => $size['h'],
            'orientation' => $size['w'] > $size['h'] ? 'L' : 'P',
            'scaled_width' => $size['w'] * $scale,
            'scaled_height' => $size['h'] * $scale
        );
        $this->template = $template;
        return $template;
    }
    
    public function addOutputPage($rotation = 0)
    {
        $this->pageRotation = $rotation;
        $this->pdf->AddPage($this->getDocument()->orientation, array(
            0 => $this->getDocument()->width,
            1 => $this->getDocument()->height,
            'Rotate' => $rotation
        ));
    }
    
    private function getDocument()
    {
        return (object)$this->document;
    }
    
    public function renderGrid($order = PDFImposerGridOrder::LeftRight_TopBottom, $template = null)
    {
        $this->generateGrid($order);
        $document = $this->getDocument();
        
        $start_point = array(
            ($document->width - $this->grid['width']) * 0.5,
            ($document->height - $this->grid['height']) * 0.5
        );
        foreach ($this->grid["items"] as $item) {
            $this->renderTemplate($start_point[0] + $item['x'], $start_point[1] + $item['y'], $template);
        }
    }
    
    public function generateGrid($order = PDFImposerGridOrder::LeftRight_TopBottom)
    {
        $this->grid = array();
        $template = $this->getSourcePageData();
        $document = $this->getDocument();
        switch ($order) {
            
            case PDFImposerGridOrder::LeftRight_TopBottom:
                $columns = range(0, $document->columns - 1);
                $rows = range(0, $document->rows - 1);
                break;
            case PDFImposerGridOrder::RightLeft_TopBottom:
                $columns = range(0, $document->columns - 1);
                $rows = range($document->rows - 1, 0);
                break;
            case PDFImposerGridOrder::LeftRight_BottomTop:
                $columns = range($document->columns - 1, 0);
                $rows = range(0, $document->rows - 1);
                break;
            case PDFImposerGridOrder::RightLeft_BottomTop:
                $columns = range($document->columns - 1, 0);
                $rows = range($document->rows - 1, 0);
                break;
            
            case PDFImposerGridOrder::TopBottom_LeftRight:
                $rows = range(0, $document->columns - 1);
                $columns = range(0, $document->rows - 1);
                break;
            case PDFImposerGridOrder::TopBottom_RightLeft:
                $rows = range(0, $document->columns - 1);
                $columns = range($document->rows - 1, 0);
                break;
            case PDFImposerGridOrder::BottomTop_LeftRight:
                $rows = range($document->columns - 1, 0);
                $columns = range(0, $document->rows - 1);
                break;
            case PDFImposerGridOrder::BottomTop_RightLeft:
                $rows = range($document->columns - 1, 0);
                $columns = range($document->rows - 1, 0);
                break;
            
            default:
                return;
        }
        
        foreach ($columns as $column) {
            foreach ($rows as $row) {
                if ($order <= PDFImposerGridOrder::RightLeft_BottomTop) {
                    $this->grid['items'][] = array(
                        'x' => $row * ($template->scaled_width + $document->spacing),
                        'y' => $column * ($template->scaled_height + $document->spacing)
                    );
                } else {
                    $this->grid['items'][] = array(
                        'y' => $row * ($template->scaled_height + $document->spacing),
                        'x' => $column * ($template->scaled_width + $document->spacing)
                    );
                }
            }
        }
        $this->grid['width'] = $document->rows * ($template->scaled_width + $document->spacing) - $document->spacing;
        $this->grid['height'] = $document->columns * ($template->scaled_height + $document->spacing) - $document->spacing;
    }
    
    private function getSourcePageData()
    {
        return (object)$this->sourcePage;
    }
    
    public function renderTemplate($x, $y, $template = null)
    {
        $this->pdf->useTemplate(
            is_null($template) ? $this->template : $template,
            $x,
            $y,
            $this->getSourcePageData()->scaled_width,
            0,
            false
        );
    }
    
    public function imposeFrontBack($scale, $order, $rotation)
    {
        $this->importSourcePageAsTemplate(1, $scale);
        $this->addOutputPage($rotation[0]);
        $this->renderGrid($order[0]);
        
        $this->importSourcePageAsTemplate(2, $scale);
        $this->addOutputPage($rotation[1]);
        $this->renderGrid($order[1]);
    }
    
    public function imposeMultipage($scale, $order, $rotation)
    {
        $document = $this->getDocument();
        $this->importSourcePageAsTemplate(1, $scale);
        
        $items_per_page = $document->rows * $document->columns;
        //var_dump($items_per_page);
        $pages = 2 * ceil($this->pageCount * 0.5 / $items_per_page);
        //var_dump($pages);
        $items = array(
            range(1, $this->pageCount, 2), //fronts
            range(2, $this->pageCount, 2) //backs
        );
        //var_dump($items);
        $rendered = array();
        
        foreach (range(1, $pages) as $page) {
            $side = (int)($page % 2 == 0); // 0 - front, 1 - back
            $this->addOutputPage($rotation[$side]);
            $this->generateGrid($order[$side]);
            $start_point = array(
                ($document->width - $this->grid['width']) * 0.5,
                ($document->height - $this->grid['height']) * 0.5
            );
            $count_items = 0;
            foreach ($items[$side] as $item) {
                if (!in_array($item, $rendered)) {
                    
                    $this->renderTemplate(
                        $start_point[0] + $this->grid['items'][$count_items]['x'],
                        $start_point[1] + $this->grid['items'][$count_items]['y'],
                        $this->importSourcePageAsTemplate($item, $scale)
                    );
                    
                    $rendered[] = $item;
                    $count_items += 1;
                    if ($count_items == $items_per_page) break;
                }
            }
        }
    }

    public function imposeMultipageNotPersonalized($scale, $order, $rotation)
    {
        $document = $this->getDocument();
        $this->importSourcePageAsTemplate(1, $scale);

        $items_per_page = $document->rows * $document->columns;
        //var_dump($items_per_page);
        $pages =  ceil($this->pageCount  / $items_per_page);
        //var_dump($pages);
        $items = array(
            range(1, $this->pageCount) //fronts
            //range(2, $this->pageCount, 2) //backs
        );
        //var_dump($items);
        $rendered = array();
        $side = 0;//(int)($page % 2 == 0); // 0 - front, 1 - back
        foreach (range(1, $pages) as $page) {
            echo $rotation[$side] + ($page === 1 ?  $this->firstPageRotation : 0);
            echo'##';
            $this->addOutputPage($rotation[$side]);
            $this->generateGrid($order[$side]);
            $start_point = array(
                ($document->width - $this->grid['width']) * 0.5,
                ($document->height - $this->grid['height']) * 0.5
            );
            $count_items = 0;
            foreach ($items[$side] as $item) {
                if (!in_array($item, $rendered)) {

                    $this->renderTemplate(
                        $start_point[0] + $this->grid['items'][$count_items]['x'],
                        $start_point[1] + $this->grid['items'][$count_items]['y'],
                        $this->importSourcePageAsTemplate($item, $scale)
                    );

                    $rendered[] = $item;
                    $count_items += 1;
                    if ($count_items == $items_per_page) break;
                }
            }
        }
        ob_end_flush();
        exit;
    }
    
}

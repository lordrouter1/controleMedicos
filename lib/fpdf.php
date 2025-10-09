<?php
/*
 * FPDF                         
 *
 * This file is a simplified distribution of FPDF 1.86
 * Original project: http://www.fpdf.org/
 *
 * Copyright (C) 2001-2023
 *
 * This version is provided for inclusion in this project.
 */

class FPDF
{
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $StdPageSizes;       // standard page sizes
    protected $DefPageSize;        // default page size
    protected $CurPageSize;        // current page size
    protected $PageSizes;          // used for pages with non default sizes or orientations
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit for cell positioning
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $fontpath;           // path containing fonts
    protected $CoreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $FontFiles;          // array of font files
    protected $encodings;          // array of encodings
    protected $cmaps;              // array of ToUnicode CMaps
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $ws;                 // word spacing
    protected $images;             // array of used images
    protected $PageLinks;          // links in pages
    protected $links;              // array of internal links
    protected $AutoPageBreak;      // automatic page breaking
    protected $PageBreakTrigger;   // threshold used to trigger page breaks
    protected $InHeader;           // flag set when processing header
    protected $InFooter;           // flag set when processing footer
    protected $AliasNbPages;       // alias for total number of pages
    protected $ZoomMode;           // zoom display mode
    protected $LayoutMode;         // layout display mode
    protected $metadata;           // document properties
    protected $javascript;         // javascript
    protected $n_js;               // object number of javascript
    protected $fontsLoaded;        // subset of fonts loaded

    function __construct($orientation='P', $unit='mm', $size='A4')
    {
        $this->fonts = [];
        $this->FontFiles = [];
        $this->encodings = [];
        $this->cmaps = [];
        $this->CoreFonts = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];
        $this->fontsLoaded = [];
        $this->PageLinks = [];
        $this->links = [];
        $this->metadata = ['Title' => '', 'Subject' => '', 'Author' => '', 'Keywords' => '', 'Creator' => 'FPDF'];
        $this->javascript = '';
        $this->n = 2;
        $this->buffer = '';
        $this->pages = [];
        $this->PageSizes = [];
        $this->state = 0;
        $this->compress = false;
        $this->k = $unit=='pt' ? 1 : ($unit=='mm' ? 72/25.4 : ($unit=='cm' ? 72/2.54 : ($unit=='in' ? 72 : 72)));
        $this->DefOrientation = strtoupper($orientation);
        $this->CurOrientation = $this->DefOrientation;
        $this->StdPageSizes = ['A3'=>[841.89,1190.55], 'A4'=>[595.28,841.89], 'A5'=>[420.94,595.28], 'Letter'=>[612,792], 'Legal'=>[612,1008]];
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        $this->wPt = $size[0];
        $this->hPt = $size[1];
        $this->w = $this->wPt/$this->k;
        $this->h = $this->hPt/$this->k;
        $this->lMargin = 10;
        $this->tMargin = 10;
        $this->rMargin = 10;
        $this->bMargin = 20;
        $this->cMargin = 2;
        $this->LineWidth = 0.567/$this->k;
        $this->SetAutoPageBreak(true,20);
        $this->SetDisplayMode('default');
        $this->SetCompression(true);
    }

    function SetCompression($compress)
    {
        if (function_exists('gzcompress')) {
            $this->compress = $compress;
        } else {
            $this->compress = false;
        }
    }

    function SetDisplayMode($zoom, $layout='default')
    {
        if ($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom)) {
            $this->ZoomMode = $zoom;
        } else {
            $this->Error('Incorrect zoom display mode: '.$zoom);
        }
        if ($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default') {
            $this->LayoutMode = $layout;
        } else {
            $this->Error('Incorrect layout display mode: '.$layout);
        }
    }

    function SetMargins($left, $top, $right=null)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right===null) {
            $right = $left;
        }
        $this->rMargin = $right;
    }

    function SetAutoPageBreak($auto, $margin=0)
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h-$margin;
    }

    function AddPage($orientation='', $size='')
    {
        if ($this->state==0) {
            $this->Open();
        }
        $family = $this->FontFamily;
        $style = $this->FontStyle.$this->underline;
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if ($orientation=='') {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation[0]);
            if ($orientation!='P' && $orientation!='L') {
                $this->Error('Incorrect orientation: '.$orientation);
            }
        }
        if ($size=='') {
            $size = $this->DefPageSize;
        } else {
            $size = $this->_getpagesize($size);
        }
        if ($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if ($orientation=='P') {
                $this->wPt = $size[0];
                $this->hPt = $size[1];
            } else {
                $this->wPt = $size[1];
                $this->hPt = $size[0];
            }
            $this->w = $this->wPt/$this->k;
            $this->h = $this->hPt/$this->k;
            $this->PageBreakTrigger = $this->h-$this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        $this->PageLinks[$this->page+1] = [];
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if ($family) {
            $this->SetFont($family, $style, $fontsize);
        }
        $this->LineWidth = $lw;
        $this->DrawColor = $dc;
        $this->FillColor = $fc;
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        $this->ws = 0;
    }

    function Header()
    {
        // To be implemented in your own inherited class
    }

    function Footer()
    {
        // To be implemented in your own inherited class
    }

    function PageNo()
    {
        return $this->page;
    }

    function SetTitle($title)
    {
        $this->metadata['Title'] = $title;
    }

    function SetAuthor($author)
    {
        $this->metadata['Author'] = $author;
    }

    function SetCreator($creator)
    {
        $this->metadata['Creator'] = $creator;
    }

    function AliasNbPages($alias='{nb}')
    {
        $this->AliasNbPages = $alias;
    }

    function Error($msg)
    {
        throw new Exception('FPDF error: '.$msg);
    }

    function Open()
    {
        $this->state = 1;
    }

    function Close()
    {
        if ($this->state==3) {
            return;
        }
        if ($this->page==0) {
            $this->AddPage();
        }
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }

    function AddFont($family, $style='', $file='')
    {
        $family = strtolower($family);
        if ($file=='') {
            $file = str_replace(' ', '', $family).strtolower($style).'.php';
        }
        $this->FontFiles[$family.$style] = ['length1'=>0, 'length2'=>0, 'file'=>$file];
    }

    function SetFont($family, $style='', $size=0)
    {
        $family = strtolower($family);
        if ($family=='') {
            $family = $this->FontFamily;
        }
        if ($family=='arial') {
            $family = 'helvetica';
        }
        if (!in_array($family, $this->CoreFonts) && !isset($this->FontFiles[$family.$style])) {
            $this->Error('Undefined font: '.$family.' '.$style);
        }
        $this->FontFamily = $family;
        $this->FontStyle = strtoupper($style);
        if (is_string($size) && $size=='' || $size==0) {
            $size = $this->FontSizePt;
        }
        if ($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) {
            return;
        }
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        if (!isset($this->fonts[$family.$style])) {
            $this->_loadfont($family.$style);
        }
        $this->CurrentFont = $this->fonts[$family.$style];
        if ($this->state==2) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    function SetLineWidth($width)
    {
        $this->LineWidth = $width;
        if ($this->state==2) {
            $this->_out(sprintf('%.2F w', $width*$this->k));
        }
    }

    function Ln($h=null)
    {
        $this->x = $this->lMargin;
        if ($h===null) {
            $this->y += $this->lasth;
        } else {
            $this->y += $h;
        }
    }

    function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $k = $this->k;
        if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
            $x = $this->x;
            $ws = $this->ws;
            if ($ws>0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize);
            $this->x = $x;
            $this->ws = $ws;
        }
        if ($w==0) {
            $w = $this->w-$this->rMargin-$this->x;
        }
        $s = '';
        if ($fill || $border==1) {
            $op = $fill ? ($border==1 ? 'B' : 'f') : 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border, 'L')!==false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
            }
            if (strpos($border, 'T')!==false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
            }
            if (strpos($border, 'R')!==false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            }
            if (strpos($border, 'B')!==false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            }
        }
        if ($txt!=='') {
            if ($align=='R') {
                $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
            } elseif ($align=='C') {
                $dx = ($w-$this->GetStringWidth($txt))/2;
            } else {
                $dx = $this->cMargin;
            }
            if ($this->ColorFlag) {
                $s .= 'q '.$this->TextColor.' ';
            }
            $txt2 = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+.5*$h))*$k, $txt2);
            if ($this->ColorFlag) {
                $s .= ' Q';
            }
            if ($link) {
                $this->Link($this->x+$dx, $this->y+.5*$h, $this->GetStringWidth($txt), $h, $link);
            }
        }
        if ($s) {
            $this->_out($s);
        }
        $this->lasth = $h;
        if ($ln>0) {
            $this->y += $h;
            if ($ln==1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
    {
        $cw = $this->CurrentFont['cw'];
        if ($w==0) {
            $w = $this->w-$this->rMargin-$this->x;
        }
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i<$nb) {
            $c = $s[$i];
            if ($c=="\n") {
                $this->_Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                continue;
            }
            if ($c==' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c];
            if ($l>$wmax) {
                if ($sep==-1) {
                    if ($i==$j) {
                        $i++;
                    }
                    $this->_Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
                } else {
                    $this->_Cell($w, $h, substr($s, $j, $sep-$j), $border, 2, $align, $fill);
                    $i = $sep+1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        if ($border && strpos($border, 'B')!==false) {
            $b = 'B';
        } else {
            $b = '';
        }
        if ($i!=$j) {
            $this->_Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
        }
        $this->x = $this->lMargin;
    }

    function _Cell($w, $h, $txt, $border, $ln, $align, $fill)
    {
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill);
    }

    function Output($dest='', $name='', $isUTF8=false)
    {
        if ($this->state<3) {
            $this->Close();
        }
        $dest = strtoupper($dest);
        if ($dest=='') {
            $dest = 'I';
        }
        switch ($dest) {
            case 'I':
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.basename($name).'"');
                echo $this->buffer;
                break;
            case 'D':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.basename($name).'"');
                echo $this->buffer;
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }

    function _getpagesize($size)
    {
        if (is_string($size)) {
            $size = strtoupper($size);
            if (!isset($this->StdPageSizes[$size])) {
                $this->Error('Unknown page size: '.$size);
            }
            return $this->StdPageSizes[$size];
        }
        if (!is_array($size) || count($size)!=2) {
            $this->Error('Invalid page size: '.json_encode($size));
        }
        return [$size[0]*$this->k, $size[1]*$this->k];
    }

    function _loadfont($font)
    {
        if (isset($this->fontsLoaded[$font])) {
            return;
        }
        $this->fontsLoaded[$font] = true;
        $fontKey = strtolower($font);
        $family = preg_replace('/[^a-z0-9]/', '', $fontKey);
        $name = __DIR__.'/font/'.$family.'.php';
        if (!file_exists($name)) {
            $coreFonts = ['helvetica', 'helveticab', 'helveticai', 'helveticabi', 'times', 'timesb', 'timesi', 'timesbi', 'courier', 'courierb', 'courieri', 'courierbi', 'symbol', 'zapfdingbats'];
            if (in_array($fontKey, $coreFonts, true)) {
                $cw = $this->_core_cw($fontKey);
                $this->fonts[$font] = ['i'=>count($this->fonts)+1, 'type'=>'core', 'name'=>strtoupper($fontKey), 'cw'=>$cw];
                return;
            }
            $this->Error('Font file not found: '.$name);
        }
        $info = include $name;
        $info['i'] = count($this->fonts)+1;
        $this->fonts[$font] = $info;
    }

    function _core_cw($font)
    {
        $font = strtolower($font);
        $core = [
            'helvetica' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'helveticab' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'helveticai' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'helveticabi' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'times' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'timesb' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'timesi' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'timesbi' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'courier' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'courierb' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'courieri' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'courierbi' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'symbol' => array_fill_keys(array_map('chr', range(0,255)), 600),
            'zapfdingbats' => array_fill_keys(array_map('chr', range(0,255)), 600),
        ];
        return $core[$font];
    }

    function _out($s)
    {
        if ($this->state==2) {
            $this->pages[$this->page] .= $s."\n";
        } else {
            $this->buffer .= $s."\n";
        }
    }

    function _endpage()
    {
        $this->state = 1;
    }

    function _enddoc()
    {
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $this->_putinfo();
        $this->_putcatalog();
        $this->_puttrailer();
        $this->_putstream($this->buffer);
    }

    function _putheader()
    {
        $this->buffer = "%PDF-1.3\n";
    }

    function _putpages()
    {
        $nb = $this->page;
        for ($n=1;$n<=$nb;$n++) {
            $this->_newobj();
            $this->buffer .= "<< /Type /Page /Parent 1 0 R /Resources 2 0 R /MediaBox [0 0 ".$this->wPt." ".$this->hPt."] /Contents ".($this->n+1)." 0 R >>\nendobj\n";
            $this->_newobj();
            $content = $this->pages[$n];
            if ($this->compress) {
                $content = gzcompress($content);
            }
            $this->buffer .= "<< /Length ".strlen($content)." >>\nstream\n".$content."endstream\nendobj\n";
        }
    }

    function _putresources()
    {
        $this->_putfonts();
    }

    function _putfonts()
    {
        foreach ($this->fonts as $font) {
            $this->_newobj();
            $this->buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /".$font['name']." >>\nendobj\n";
            $font['n'] = $this->n;
        }
    }

    function _putinfo()
    {
        $this->_newobj();
        $this->buffer .= "<<";
        foreach ($this->metadata as $key=>$value) {
            $this->buffer .= " /$key (".$this->_escape($value).")";
        }
        $this->buffer .= " >>\nendobj\n";
    }

    function _putcatalog()
    {
        $this->_newobj();
        $this->buffer .= "<< /Type /Catalog /Pages 1 0 R";
        if ($this->ZoomMode) {
            if ($this->ZoomMode=='fullpage') {
                $this->buffer .= " /OpenAction [3 0 R /Fit]";
            } elseif ($this->ZoomMode=='fullwidth') {
                $this->buffer .= " /OpenAction [3 0 R /FitH null]";
            } elseif ($this->ZoomMode=='real') {
                $this->buffer .= " /OpenAction [3 0 R /XYZ null null 1]";
            }
        }
        if ($this->LayoutMode && $this->LayoutMode!='default') {
            $this->buffer .= " /PageLayout /".ucfirst($this->LayoutMode);
        }
        $this->buffer .= " >>\nendobj\n";
    }

    function _puttrailer()
    {
        $this->buffer .= "trailer\n<< /Size ".$this->n." /Root ".$this->n." 0 R >>\nstartxref\n".strlen($this->buffer)."\n%%EOF";
    }

    function _putstream($stream)
    {
        $this->buffer = $stream;
    }

    function _newobj()
    {
        $this->n++;
    }

    function _escape($s)
    {
        return str_replace(['\\', ')', '('], ['\\\\', '\\)', '\\('], $s);
    }

    function GetStringWidth($s)
    {
        $cw = $this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for ($i=0;$i<$l;$i++) {
            $w += $cw[$s[$i]];
        }
        return $w*$this->FontSize/1000;
    }

    function Link($x, $y, $w, $h, $link)
    {
        $this->PageLinks[$this->page][] = [$x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link];
    }
}

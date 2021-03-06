<?php

/* 
*********************************************************************
Copyright Kevin Donnelly 2012-2014.
kevindonnelly.org.uk
This file is part of Andika!, a set of tools for writing Swahili in Arabic script..

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License or the GNU
Affero General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any 
later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
and the GNU Affero General Public License along with this program.
If not, see <http://www.gnu.org/licenses/>.
*********************************************************************
*/

/*
This script converts Swahili poems in Arabic script into Roman script, and vice versa, adding an automatically
generated transcription.  There are many options available, so see the manual for full details.
*/


// -----------------------------
// Initialise required stuff.
// -----------------------------
include("./andika/config.php");
include("./includes/fns.php");
require_once 'convert/QueryPath-2.1.2-minimal/QueryPath.php';


// ---------------------------------------------------------------------------
// Handle input coming from convert.sh or from command-line
// ---------------------------------------------------------------------------
$collection=explode("+", $argv[1]);
// print_r($collection);

list($input, $script, $genre, $output, $layout, $transtxt)=$collection;

$pathparts = pathinfo($input);
$inpath=$pathparts["dirname"];  // path
$input=$pathparts["basename"];  // filename
$poem=$pathparts["filename"];  // filename without the extension
$type=$pathparts["extension"];  // extension

$script=lcfirst($script);
$genre=lcfirst($genre);
$columns=($layout=="vip-space") ? "rrl" : "rl";

$stanza_no=0;  // stanza counter.  If handling an excerpt, this can be set to one below the lowest stanza number of the excerpt, to allow the numbering to be output correctly.


// --------------------------------------------------------
// Locate the input file, depending on extension.
// --------------------------------------------------------
if ($type=="odt")
{
    $file="zip://".$inpath."/".$input."#content.xml";
}
elseif ($type=="txt")
{
    $file=$inpath."/".$input;
}


// -----------------------------------------
// Set up a dir for each file's output.
// -----------------------------------------
exec("mkdir -p convert/outputs/".$poem);


// -----------------------------------------------------------
// If database import is chosen, set up a table for each file.
// -----------------------------------------------------------
if ($output=="db")
{
    include("convert/create_poemlines.php");
}


// ------------------------------------------------------------------------
// Read the lines in the file into an array, based on extension.
// ------------------------------------------------------------------------
if ($type=="odt")
{
    foreach (qp($file)->find('text|p') as $line) 
    {
        $poemlines[]=$line->text();
    }
}
elseif ($type=="txt")
{    
    $poemlines=file($file);
}

// print_r($poemlines);


// --------------------------
// Open the output file.
// --------------------------
if ($output=="pdf")
{
    $fp = fopen("convert/outputs/$poem/{$poem}.tex", "w") or die("Can't create the file");
    $header=file_get_contents("convert/tex/tex_header.tex");
    fwrite($fp, $header);
    fwrite($fp, "\n");
    if ($genre=="prose")
    {
        fwrite($fp, "\\begin{flushright}\n\n");
    }
    elseif ($genre=="poetry")
    {
        fwrite($fp, "\begin{longtable}{{$columns}} \n\n");
        if ($layout=="vip-space")
        {
            fwrite($fp, "\makebox[8cm][r]{} & & \makebox[8cm][r]{} \\\\ \n\n"); 
        }
        else
        {
            fwrite($fp, "\makebox[8cm][r]{} & \\\\ \n\n"); 
        }
    }
}
elseif ($output=="txt")
{
    $fp=fopen("convert/outputs/$poem/{$poem}.txt", "w") or die("Can't create the file");
}
elseif ($output=="odt")
{
    $fp=fopen("convert/odt/content.xml", "w") or die("Can't create the file");
    $header=file_get_contents("convert/odt/odt_header.txt");
    fwrite($fp, $header);
}


// --------------------------------
// Import and choose layout
// --------------------------------
$vipande=array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');  // letters to signify location of the kipande in the stanza
// or: $vipande=range('a', 'z');
$first_half=array('a', 'c', 'e', 'g', 'i', 'k', 'm', 'o', 'q', 's', 'u', 'w', 'y');  // vipande which signify the beginning of a line
$second_half=array('b', 'd', 'f', 'h', 'j', 'l', 'n', 'p', 'r', 't', 'v', 'x', 'z');  // vipande which signify the beginning of a line

foreach ($poemlines as $key=>$poemline)
{
   //echo $poemline;
   if (strlen(trim($poemline)) > 0) // there's text on the line ...  ("empty" lines in a txt file contain a \n character, so we have to trim that off)
    {
	if (preg_match("/#/", $poemline))
	{
	    $msno=substr(trim($poemline), 1);
	}
	else 
	{
	    $stanza_contents[]=$poemline;  // ... so put it into an array
	}
    }
    else  // the line is blank, so print out what we have (the previous stanza for poetry, the whole text for prose)
    {
	$stanza_no++;  // increment the stanza number
	echo "\n";  // add a blank line in the feedback
	
        if ($genre=="poetry")
        {
	    //print_r($stanza_contents);
	    //echo count($stanza_contents)."\n";
	    // truncate $vipande to the length of $stanza_contents
	    array_splice($vipande, count($stanza_contents));
	    //print_r($vipande);
	    // set values of $vipande as keys of $stanza_contents - keys are then letters instead of numbers
	    $stanza_contents=array_combine($vipande, $stanza_contents);
	    
	    // Set up check for stanzas with odd numbers of lines.
	    end($stanza_contents);  // move the internal pointer to the end of the array
	    $lastkey = key($stanza_contents);  // fetch the key of the last item in the array
	    reset($stanza_contents);  // move the pointer back to the first item of the array
	}

        foreach ($stanza_contents as $key=>$stanza_line)
        {
	    if ($genre=="prose")
            {
                unset($stanza_no);
                $key=$key+1;
                                
		include("layouts/kip-line_{$script}.php");
            }
            elseif ($genre=="poetry")
            {
		if ($layout=="vip-space")
		{
		    include("layouts/vip-space_{$script}.php");
		}
		elseif ($layout=="vip-star")
		{
		    include("layouts/vip-star_{$script}.php");
		} 
		elseif ($layout=="kip-line")
		{
		    include("layouts/kip-line_{$script}.php");
		}
	    }
	}
	    
	// Insert a spacer between stanzas, depending on extension.
	if ($output=="pdf")
	{
	    fwrite($fp, "\\\\[8mm] \n\n");
	}
	elseif ($output=="txt")
	{
	    fwrite($fp, "\n");
	}
	elseif ($output=="odt")
	{
	    fwrite($fp, "<text:p text:style-name=\"Arabic\"/>\n\n");
	}
	    
	unset($stanza_contents);
    }
}


// --------------------------------------------------------
// Close off the output, depending on extension.
// --------------------------------------------------------
if ($output=="pdf")
{
    if ($genre=="prose")
    {
        fwrite($fp, "\\end{flushright}\n\n");
    }
    elseif ($genre=="poetry")
    {
        fwrite($fp, "\\end{longtable} \n\n");
    }
    fwrite($fp, "\\end{document}\n");
    fclose($fp);

    // Compile the tex file into a pdf.
    exec("xelatex -interaction=nonstopmode -output-directory=convert/outputs/$poem convert/outputs/$poem/{$poem}.tex 2>&1");
}
elseif ($output=="txt")
{
    fclose($fp);
}
elseif ($output=="odt")
{
    include("odt/odt_close.php");
}

?>
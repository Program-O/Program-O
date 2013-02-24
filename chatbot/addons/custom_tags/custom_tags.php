<?php
/***************************************
* www.program-o.com
* PROGRAM O 
* Version: 2.1.2
* FILE: custom_tags.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: MAY 4TH 2011
* DETAILS: this file contains the addon library to process the custom <code> tag
***************************************/
include('code_tag/code_tag.php');


function custom_parse_aiml_as_XML($find,$replace,$j)
{
	//custom code aiml tags 
	//found in code_tags/code_tags.php
	$j++;
	$find[$j]='#<code>([^<]*)</code>#i';
	$replace[$j]='\'.just_run_code(\'$1\').\'';

	$j++;
	$find[$j]='#<ban>([^<]*)</ban>#i';
	$replace[$j]='\'.add_to_ban(\'$1\').\'';


	$mergeArr['find']=$find;
	$mergeArr['replace']=$replace;	
	
	return $mergeArr;

}
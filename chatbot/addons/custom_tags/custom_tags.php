<?php
/***************************************
* www.program-o.com
* PROGRAM O 
* Version: 2.0.1
* FILE: custom_tags.php
* AUTHOR: ELIZABETH PERREAU
* DATE: MAY 4TH 2011
* DETAILS: this file contains the addon library to spell check into before its matched in the database
***************************************/
include('code_tag/code_tag.php');


function custom_aiml_to_phpfunctions($find,$replace,$j)
{
	//custom code aiml tags 
	//found in code_tags/code_tags.php
	$j++;
	$find[$j]='#<code>([^<]*)</code>#i';
	$replace[$j]='\'.call_user_func(\'just_run_code\',\'$1\').\'';	


	$mergeArr['find']=$find;
	$mergeArr['replace']=$replace;	
	
	return $mergeArr;

}
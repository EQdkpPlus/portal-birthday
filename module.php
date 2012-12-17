<?php
/*
 * Project:     EQdkp-Plus
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-12-03 22:20:35 +0100 (Do, 03. Dez 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: wallenium $
 * @copyright   2006-2008 Corgan - Stefan Knaak | Wallenium & the EQdkp-Plus Developer Team
 * @link        http://eqdkp-plus.com
 * @package     eqdkp-plus
 * @version     $Rev: 6570 $
 *
 * $Id: module.php 6570 2009-12-03 21:20:35Z wallenium $
 */

if ( !defined('EQDKP_INC') ){
  header('HTTP/1.0 404 Not Found');exit;
}

$portal_module['birthday'] = array(
			'name'           => 'Birthdays',
			'path'           => 'birthday',
			'version'        => '1.0.0',
			'author'         => 'WalleniuM',
			'contact'        => 'http://www.eqdkp-plus.com',
			'description'    => 'Shows the actual birthdays on that day',
			'positions'      => array('left1', 'left2', 'right'),
      'signedin'       => '1',
      'install'        => array(
			                      'autoenable'        => '1',
			                      'defaultposition'   => 'left2',
			                      'defaultnumber'     => '10',
			                    ),
    );

$portal_settings['birthday'] = array(
  'pk_birthday_limit'     => array(
    'name'      => 'pk_birthday_limit',
    'language'  => 'pk_birthday_limit',
    'property'  => 'text',
    'size'      => '2',
  ),
);

if(!function_exists(birthday_module))
{
  function birthday_module()
  {
  	global $eqdkp, $plang, $pcache, $pm, $db, $eqdkp_root_path, $conf_plus, $pdc;
  	$show_birthdays = ($conf_plus['pk_birthday_limit'] > 0) ? $conf_plus['pk_birthday_limit'] : 5;

  	$myOut = $pdc->get('portal.modul.birthday',false,true);

  	if (!$myOut)
  	{
	  	// Load birthdays
	    $birt_sql     = 'SELECT user_id, username, birthday	FROM __users ORDER BY birthday';
	    $birt_result  = $db->query($birt_sql);
	    $myBirthdays = '';
	    while ( $brow = $db->fetch_record($birt_result))
	    {
	      if($brow['birthday'])
	      {
	        $tmpbdtimestamp = birthday_2timestamp($brow['birthday']);
	        $sortdate       = birthday_sortdate($tmpbdtimestamp);
	        $myBirthdays[] = array(
	                                          'username'  => $brow['username'],
	                                          'birthday'  => $tmpbdtimestamp,
	                                          'age'       => birthday_age($brow['birthday']),
	                                          'today'     => birthday_istoday($sortdate) ? true : false,
	                                          'sortdate'  => $sortdate
	                                        );
	      }
	    }

	    if(is_array($myBirthdays))
	    {
		    foreach ($myBirthdays as $key => $row) {
		      $bdsort[$key]    = $row['sortdate'];
		    }
	    	array_multisort($bdsort,SORT_ASC,$myBirthdays);
	    }

	    // Generate Output
	  	$myOut = "<table cellpadding='3' cellSpacing='2' width='100%'>";
	  	if(is_array($myBirthdays) && count($myBirthdays) > 0)
	  	{
	      $ciii = 0;
	      foreach($myBirthdays as $boptions)
	      {
	        $highlight = ($boptions['today']) ? "class='birthday_today'" : "class='".$eqdkp->switch_row_class()."'";
	        $bdicon    = ($boptions['today']) ? "<img src='{$eqdkp_root_path}portal/birthday/images/cake.png' /> ": '';
	        if($show_birthdays > $ciii){
	          $myOut .= "<tr valign='top' ".$highlight.">
	                        <td>
	                          <table cellpadding='0' cellSpacing='0' width='100%'>
	                            <tr>
	                            <td class='birthday_username' style='font-weight:bold;'>
	                              ".$bdicon.$boptions['username']."
	                            </td>
	                            <td class='birthday_date' align='right'>
	                              ".date('d.m', $boptions['birthday'])."
	                            </td>
	                            <td class='birthday_date' align='right' width='30px;'>
	                              (".$boptions['age'].")
	                            </td>
	                          </tr>
	                          </table>
	                        </td>
	                      </tr>";
	        }
	        $ciii++;
	      }
	    }else
	    {
	      $myOut .= "<tr valign='top' class='".$eqdkp->switch_row_class()."'>
	                  <td>".$plang['pk_birthday_nobd']."</td>
	                </tr>";
	    }

	  	$myOut .= "</table>";

	  	$pdc->put('portal.modul.birthday',$myOut,86400,false,true);
  	}

  	return $myOut;
  }

  function birthday_age($birthdate) {
    list($day,$mon,$year) = explode(".",$birthdate);
    $today = getdate(time());
    $yeardiff = ($today['mon'] > $mon) ? ($today['year']+1) - $year : $today['year'] - $year;
    return($yeardiff);
  }

  function birthday_sortdate($timestamp){
    $today    = getdate(time());
    $birthday = getdate($timestamp);

    // Ok.. this is tricky: if the birthday month is < now, change year+1!
    if($birthday['mon'] > $today['mon'] || ($birthday['mon'] == $today['mon'] && $birthday['mday'] >= $today['mday'])){
      $year = $today['year'];
    }else{
      $year = $today['year']+1;
    }
    return mktime(0,0,0,$birthday['mon'],$birthday['mday'],$year);
  }

  function birthday_2timestamp($birthdate){
    list($day,$mon,$year) = explode(".",$birthdate);
    return mktime(0,0,0,$mon,$day,$year);
  }

  function birthday_istoday($timestamp){
    $birthday = getdate($timestamp);
    $today    = getdate(time());
    if($birthday['mon'] == $today['mon'] && $today['mday'] == $birthday['mday']){
      return 1;
    }else{
      return 0;
    }
  }
}
?>

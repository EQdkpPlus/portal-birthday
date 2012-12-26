<?php
 /*
 * Project:		EQdkp-Plus
 * License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:		2008
 * Date:		$Date: 2012-12-17 12:50:34 +0100 (Mo, 17. Dez 2012) $
 * -----------------------------------------------------------------------
 * @author		$Author: godmod $
 * @copyright	2006-2011 EQdkp-Plus Developer Team
 * @link		http://eqdkp-plus.com
 * @package		eqdkp-plus
 * @version		$Rev: 12603 $
 *
 * $Id: birthday_portal.class.php 12603 2012-12-17 11:50:34Z godmod $
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class birthday_portal extends portal_generic {
	public static function __shortcuts() {
		$shortcuts = array('user', 'pdc', 'core', 'db', 'time', 'config');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

	protected $path		= 'birthday';
	protected $data		= array(
		'name'			=> 'Birthdays',
		'version'		=> '2.0.0',
		'author'		=> 'WalleniuM',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Shows the actual birthdays on that day',
	);
	protected $positions = array('left1', 'left2', 'right');
	protected $settings	= array(
		'pk_birthday_limit'	=> array(
			'name'		=> 'pk_birthday_limit',
			'language'	=> 'pk_birthday_limit',
			'property'	=> 'text',
			'size'		=> '2',
		),
	);
	protected $install	= array(
		'autoenable'		=> '1',
		'defaultposition'	=> 'left2',
		'defaultnumber'		=> '10',
		'visibility'		=> array(2,3,4),
	);
	
	protected $reset_pdh_hooks = array('user');

	public function output() {
		$show_birthdays = ($this->config->get('pk_birthday_limit') > 0) ? $this->config->get('pk_birthday_limit') : 5;
		$myOut = $this->pdc->get('portal.modul.birthday.'.$this->root_path,false,true);

		if (!$myOut){
			// Load birthdays
			$birt_sql		= 'SELECT user_id, username, birthday FROM __users ORDER BY birthday';
			$birt_result	= $this->db->query($birt_sql);
			$myBirthdays	= '';
			while ( $brow = $this->db->fetch_record($birt_result)){
				if(!empty($brow['birthday'])){
					$sortdate		= $this->birthday_sortdate($brow['birthday']);
					$myBirthdays[] = array(
						'user_id'		=> $brow['user_id'],
						'username'		=> $brow['username'],
						'birthday'		=> $brow['birthday'],
						'age'			=> $this->time->age($brow['birthday']),
						'today'			=> $this->birthday_istoday($brow['birthday']) ? true : false,
						'sortdate'		=> $sortdate
					);
				}
			}

			if(is_array($myBirthdays)){
				foreach ($myBirthdays as $key => $row) {
					$bdsort[$key]		= $row['sortdate'];
				}
				array_multisort($bdsort,SORT_ASC,$myBirthdays);
			}

			// Generate Output
			$myOut = "<table cellpadding='3' cellspacing='2' width='100%' class='colorswitch'>";
			if(is_array($myBirthdays) && count($myBirthdays) > 0){
				$ciii = 0;
				foreach($myBirthdays as $boptions){
					$highlight = ($boptions['today']) ? "class='birthday_today'" : "";
					$bdicon    = ($boptions['today']) ? "<img src='{$this->root_path}portal/birthday/images/cake.png' alt='Birthday' /> ": '';
					if(!$boptions['today']) $boptions['age']++;
					if($show_birthdays > $ciii){
						$myOut .= "<tr valign='top' ".$highlight.">
									<td>
										<table cellpadding='0' cellspacing='0' width='100%'>
											<tr>
												<td class='birthday_username' style='font-weight:bold;'>
													".$bdicon.'<a href="'.$this->root_path.'listusers.php'.$this->SID.'&amp;u='.$boptions['user_id'].'">'.$boptions['username'].'</a>'."
												</td>
												<td class='birthday_date' align='right'>
													".$this->time->date('d.m.', $boptions['birthday'])."
												</td>
												<td class='birthday_date' align='right' width='30'>
													(".$boptions['age'].")
												</td>
											</tr>
										</table>
									</td>
									</tr>";
					}
					$ciii++;
				}
			}else{
				$myOut .= "<tr valign='top'>
					<td>".$this->user->lang('pk_birthday_nobd')."</td>
					</tr>";
			}
			$myOut .= "</table>";
			$this->pdc->put('portal.modul.birthday.'.$this->root_path,$myOut,86400,false,true);
		}
		return $myOut;
	}

	private function birthday_sortdate($timestamp){
		$today		= $this->time->getdate();
		$birthday	= $this->time->getdate($timestamp);

		// Ok.. this is tricky: if the birthday month is < now, change year+1!
		if($birthday['mon'] > $today['mon'] || ($birthday['mon'] == $today['mon'] && $birthday['mday'] >= $today['mday'])){
			$year = $today['year'];
		}else{
			$year = $today['year']+1;
		}
		return $this->time->mktime(0,0,0,$birthday['mon'],$birthday['mday'],$year);
	}

	private function birthday_istoday($timestamp){
		$birthday	= $this->time->getdate($timestamp);
		$today		= $this->time->getdate();
		if($birthday['mon'] == $today['mon'] && $today['mday'] == $birthday['mday']){
			return 1;
		}else{
			return 0;
		}
	}

	public function reset() {
		$this->pdc->del_prefix('portal.modul.birthday');
	}
}
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_birthday_portal', birthday_portal::__shortcuts());
?>
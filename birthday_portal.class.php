<?php
/*	Project:	EQdkp-Plus
 *	Package:	Birthday Portal Module
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class birthday_portal extends portal_generic {

	protected static $path		= 'birthday';
	protected static $data		= array(
		'name'			=> 'Birthdays',
		'version'		=> '2.2.1',
		'author'		=> 'WalleniuM',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Shows the actual birthdays on that day',
		'lang_prefix'	=> 'birthday_',
		'icon'			=> 'fa-gift'
	);
	protected static $positions = array('left1', 'left2', 'right');
	protected $settings	= array(
		'limit'	=> array(
			'type'		=> 'text',
			'size'		=> '2',
		),
	);
	protected static $install	= array(
		'autoenable'		=> '1',
		'defaultposition'	=> 'left2',
		'defaultnumber'		=> '10',
		'visibility'		=> array(2,3,4),
	);
	protected static $apiLevel = 20;

	protected $reset_pdh_hooks = array('user');

	public function output() {
		$show_birthdays = ($this->config('limit') > 0) ? $this->config('limit') : 5;
		$myBirthdays = $this->pdc->get('portal.module.birthday.'.$this->user->id,false,true);
		$userTimeFormat = $this->user->style['date_notime_short'];
		//Try to remove the year
		$userTimeFormat = str_ireplace(array('.y', 'y.', '-y', 'y-', '/y', 'y/'), '', $userTimeFormat);

		if (!$myBirthdays){
			// Load birthdays
			$arrUserIDs = $this->pdh->get('user', 'id_list', array());
			$myBirthdays	= array();
			foreach($arrUserIDs as $intUserID){
				$intBirthday = $this->pdh->get('user', 'birthday', array($intUserID));
				if($intBirthday === 0 || !$intBirthday) continue;
				$sortdate		= $this->birthday_sortdate($intBirthday);

				$myBirthdays[] = array(
						'user_id'		=> $intUserID,
						'username'		=> $this->pdh->get('user', 'name', array($intUserID)),
						'birthday'		=> $intBirthday,
						'age'			=> $this->time->age($intBirthday),
						'today'			=> $this->birthday_istoday($intBirthday) ? true : false,
						'sortdate'		=> $sortdate
				);
			}

			if(is_array($myBirthdays)){
				$bdsort = array();
				foreach ($myBirthdays as $key => $row) {
					$bdsort[$key]		= $row['sortdate'];
				}
				array_multisort($bdsort,SORT_ASC,$myBirthdays);
			}
			$this->pdc->put('portal.module.birthday.'.$this->user->id,$myBirthdays,3600,false,true);
		}

		$myOut = '<div class="table colorswitch hoverrows">';
		if(is_array($myBirthdays) && count($myBirthdays) > 0){
			$ciii = 0;
			foreach($myBirthdays as $boptions){
				$highlight = ($boptions['today']) ? " birthday_today" : "";
				$bdicon    = ($boptions['today']) ? "<i class='fa fa-gift fa-lg ga-fw'></i> ": '';
				if(!$boptions['today']) $boptions['age']++;
				if($show_birthdays > $ciii){

					$myOut .= '<div class="tr'.$highlight.'">
									<div class="td birthday_username" style="font-weight:bold;">
										'.$bdicon.'<a href="'.$this->routing->build('user', $boptions['username'], 'u'.$boptions['user_id']).'">'.$boptions['username'].'</a>
									</div>
									<div class="td birthday_date">'.$this->time->date($userTimeFormat, $boptions['birthday']).'</div>
									<div class="td birthday_age">('.$boptions['age'].')</div>
								</div>';
				}
				$ciii++;
			}
		}else{
			$myOut .= '<div class="tr">
				<div class="td">'.$this->user->lang('birthday_nobd').'</div>
				</div>';
		}
		$myOut .= "</div>";
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

	public static function reset() {
		register('pdc')->del_prefix('portal.module.birthday');
	}
}
?>

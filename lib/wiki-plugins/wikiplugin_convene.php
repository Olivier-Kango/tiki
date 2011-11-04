<?php                                                                                  
// (c) Copyright 2002-2011 by authors of the Tiki Wiki CMS Groupware Project           
//                                                                                     
// All Rights Reserved. See copyright.txt for details and a complete list of authors.  
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.  
// $Id$

function wikiplugin_convene_info() {
	return array(
		'name' => tra('Convene'),
		'documentation' => 'PluginConvene',
		'description' => tra('Convene an event with schedule and members'),
		'prefs' => array('wikiplugin_convene','feature_calendar'),
		'body' => tra('Convene Data'),
		'icon' => 'pics/icons/arrow_in.png',
		'filter' => 'rawhtml_unsafe',
		'tags' => array( 'basic' ),	
		'params' => array(
			'title' => array(
				'required' => false,
				'name' => tra('Title of event'),
				'default' => tra("Convene")
			),
		),
	);
}

function wikiplugin_convene($data, $params) {
	global $tikilib, $headerlib, $page, $caching, $tiki_p_edit;

	static $htmlFeedLinkI = 0;
	++$conveneI;
	$i = $conveneI;
	
	$params = array_merge(array(
		"title" => "Convene",
	), $params);
	
	extract ($params,EXTR_SKIP);
	$dates = array();
	
	//start flat static text to prepared array
	$lines = explode("\n", trim($data));
	sort($lines);
	foreach($lines as $line) {
		$line = trim($line);
		
		$parts = explode(':', $line);
		$dates[trim($parts[0])] = trim($parts[1]);
	}
	
	$dates = TikiFilter_PrepareInput::delimiter('_')->prepare($dates);
	//end flat static text to prepared array
	
	
	//start get users from array
	$users = array();
	foreach(end($dates['dates']) as $user => $vote) {
		$users[] = $user;
	}
	//end get users from array
	
	
	//start votes summed together
	$votes = array();
	foreach($dates['dates'] as $stamp => $date) {
		foreach($date as $vote) {
			$votes[$stamp] += $vote;
		}
	}
	//end votes summed together
	
	
	//start find top vote stamp
	$topVoteStamp = 0;
	foreach($votes as $stamp => $vote) {
		$topVoteStamp = ($vote > $votes[$topVoteStamp] ? $stamp : $topVoteStamp);
	}
	//end find top vote stamp
	
	
	//start reverse array for easy listing as table
	$rows = array();
	foreach($dates['dates'] as $stamp => $date) {
		foreach($date as $user => $vote) {
			if (isset($rows[$user][$stamp])) $rows[$user][$stamp] = array();
			 
			$rows[$user][$stamp] = $vote;
		}
	}
	//end reverse array for easy listing as table
	
	$result = "";
	
	//start date header
	$dateHeader = "";
	foreach($votes as $stamp => $totals) {
		$dateHeader .= "<td>". $tikilib->get_long_datetime($stamp) .
			($tiki_p_edit == 'y' ? " <img src='pics/icons/delete.png' class='conveneDeleteDate$i icon' data-date='$stamp' title='" . tr("Delete Date") . "'/>" : "").
		"</td>";
	}
	$result .= "
		<tr>
			<td style='text-align: center; padding: 3px;'>".
				(
				$tiki_p_edit == 'y'
					? 
					"<div class='ui-widget-header ui-corner-all'>
						<img src='pics/icons/user.png' id='conveneAddUser$i' class='icon' title='Add User' />
						<img src='pics/icons/calendar_add.png' id='conveneAddDate$i' class='icon' title='Add Date' />
					</div>"
					: ""
				).
			"</td>
			$dateHeader
		</tr>";
	//end date header
	
	
	//start user list and votes 
	$userList = "";
	foreach($rows as $user => $row) {
		$userList .= "<tr class='conveneUserVotes$i'>";
		$userList .= "<td>". ($tiki_p_edit == 'y' ? "<img src='pics/icons/pencil.png' class='conveneUpdateUser$i icon' title='" . tr("Edit User") . "' />" : "") . $user . "</td>";
		foreach($row as $stamp => $vote) {
			$class = 	'ui-state-default ui-state-active';
			$text = 	($vote  == 1 ? "<img src='pics/icons/tick.png' alt='OK' class='vote' />" : "<img src='pics/icons/cross.png' class='vote' />" );
			
			$userList .= "<td class='$class'>". $text
				."<input type='hidden' name='dates_" . $stamp . "_" . $user . "' value='$vote' class='conveneUserVote$i' />"
				."</td>";
		}
		$userList .= "</tr>";
	}
	$result .= $userList;
	//end user list and votes
	
	
	//start add new user and votes
	$result .= "<tr><td />";
	//end add new user and votes
	
	
	//start last row with auto selected date(s)
	$lastRow = "";
	foreach($votes as $stamp => $total) {
		$pic = "";
		if ($total == $votes[$topVoteStamp]) {
			$pic .= ($tiki_p_edit != "y" ? "<img src='pics/icons/tick.png' class='icon' title='" . tr("Selected Date") . "' />" : "");
			if ($tiki_p_edit == 'y') {
				$pic .= "<a href='tiki-calendar_edit_item.php?todate=$stamp&calendarId=1' title='" . tr("Add as Calendar Event") . "'><img src='pics/icons/calendar_add.png' class='icon' /></a>";
			}
		}
		
		$lastRow .= "<td>". $total ."&nbsp;$pic</td>";
	}
	$result .= $lastRow . "</tr>";
	//end last row with auto selected date(s)
	
	
	$result = <<<FORM
			<form id='pluginConvene$i'>
				<table cellpadding="2" cellspacing="2" border="0" style="width: 100%;">$result</table>
			</form>
FORM;
	
	$conveneData = json_encode(array(
		"dates" => $dates,
		"users" => $users,
		"votes" => $votes,
		"topVote" => $votes[$topVoteStamp],
		"rows" =>	$rows,
		"data" => $data,
	));
	
	$n = '\n\r';
	
	$headerlib->add_jsfile("lib/jquery/jquery-ui-timepicker-addon.js");
	$headerlib->add_jq_onready(<<<JQ
		
		var convene$i = $.extend({
			fromBlank: function(user, date) {
				if (!user || !date) return;
				this.data = "dates_" + Date.parseUnix(date) + "_" + user;
				this.save();
			},
			updateUsersVotes: function() {
				var dates = [];
				$('.conveneUserVotes$i').each(function() {
					$('.conveneUserVote$i').each(function() {
						dates.push($(this).attr('name') + ' : ' + $(this).val());
					});
				});
				
				this.data = dates.join('$n');
				
				this.save();
			},
			addUser: function(user) {
				if (!user) return;
				
				var dates = [];
				
				for(date in this.dates.dates) {
					dates.push("dates_" + date + "_" + user);
				}
				
				this.data += '$n' + dates.join('$n');
				
				this.save();
			},
			addDate: function(date) {
				if (!date) return;
				date = Date.parseUnix(date);
				var addedData = '';
				
				for(user in this.users) {
					addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
				}
				
				this.data = (this.data + '$n' + addedData).split(/$n/).sort();
				
				//remove empty lines
				for(line in this.data) {
					if (!this.data[line]) this.data.splice(line, 1);
				}
				
				this.data = this.data.join('$n');
				
				this.save();
			},
			deleteDate: function(date) {
				if (!date) return;
				var addedData = '';
				
				for(user in this.users) {
					addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
				}
				
				var lines = convene$i.data.split(/$n/);
				var newData = [];
				for(line in lines) {
					if (!(lines[line] + '').match(date)) {
						 newData.push(lines[line]);
					}
				}
				
				this.data = newData.join('$n');
				
				this.save();
			},
			save: function() {
				var data = this.data;
				$.modal(tr("Loading..."));
				$(document)
					.unbind('plugin_convene_ready')
					.one('plugin_convene_ready', function(e) {
						var content = $(e.container).find('[name="content"]');
						content.val($.trim(data));
						e.btns.Submit();
					});
				
				$('#plugin-edit-convene$i').click();
			}
		}, $conveneData);
		
		
		//handle a blank convene
		$('#conveneBlank$i').each(function() {
			var table = $('<table>' +
				'<tr>' +
					'<td>' +
						'User: <input style="width: 100px;" id="conveneNewUser$i" />' +
					'</td>' +
					'<td>' +
						'Date/Time: <input style="width: 100px;" id="conveneNewDatetime$i" />' +
					'</td>' +
					'<td style="vertical-align: middle;">' +
						'<input type="button" id="conveneNewUserAndDate$i" value="' + tr("Add User & Date") + '" />' +
					'</td>' +
				'</tr>' +
			'</table>').appendTo(this);

			$('#conveneNewDatetime$i').datetimepicker();
			
			$('#conveneNewUserAndDate$i').click(function() {
				convene$i.fromBlank($('#conveneNewUser$i').val(), $('#conveneNewDatetime$i').val());
			});
		});
			
		
		$('#conveneAddDate$i').click(function() {
			var o = $('<div><input type="text" style="width: 100%;" /></div>')
				.dialog({
					modal: true,
					title: "Add Date",
					buttons: {
						"Add" : function(){
							convene$i.addDate(o.find('input:first').val());
						}
					}
				});
			
			o.find('input:first')
				.datetimepicker()
				.focus();
		});
		
		$('.conveneDeleteDate$i')
			.click(function() {
				convene$i.deleteDate($(this).data("date"));
			});
		
		$('.conveneUpdateUser$i').toggle(function() {
			$('.conveneUpdateUser1').not(this).hide();
			$(this).attr('src', 'pics/icons/accept.png');
			var parent = $(this).parent().parent();
			parent.find('.vote').hide();
			parent.find('input').each(function() {
				$('<input type="checkbox" value="1"/>')
					.attr('checked', ($(this).val() == 1 ? true : false))
					.insertAfter(this)
					.click(function() {
						convene$i.updateUsers = true;
					});
			});
		}, function () {
			$('.conveneUpdateUser1').show();
			$(this).attr('src', 'pics/icons/pencil.png');
			var parent = $(this).parent().parent();
			parent.find('.vote').show();
			parent.find('input:checkbox').each(function(i) {
				parent.find('input.conveneUserVote$i').eq(i).val( $(this).is(':checked') ? 1 : 0);
				
				$(this).remove();
			});
			
			if (convene$i.updateUsers) {
				convene$i.updateUsersVotes();
			}
		});
		
		$('#conveneAddUser$i').click(function() {
			var o = $('<div><input type="text" style="width: 100%;" /></div>')
				.dialog({
					title: "User Name",
					modal: true,
					buttons: {
						"Add": function() {
							convene$i.addUser(o.find('input:first').val());
						}
					}
				})
		});
		
		$('#pluginConvene$i .icon').css('cursor', 'pointer');
JQ
);
	
	if (empty($data)) {
		$result = "<div id='conveneBlank$i'></div>";
	}
	
	return
<<<RETURN
~np~
	<div class="ui-widget-content ui-corner-all">
		<div class="ui-widget-header ui-corner-top">
			<h5 style="margin: 5px;">$title</h5>
		</div>
			$result
	</div>
~/np~
RETURN;
}
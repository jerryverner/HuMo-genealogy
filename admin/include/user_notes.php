<?php
@set_time_limit(3000);
@ini_set('memory_limit','-1');

// *** Safety line ***
if (!defined('ADMIN_PAGE')){ exit; }

echo '<h1 align=center>'.__('User notes').'</h1>';

echo '<table class="humo standard"  border="1">';

echo '<tr class="table_header"><th colspan="2">'.__('User notes').'</th></tr>';

	echo '<tr><td>'.__('Choose family tree').'</td>';
	echo '<td>';
		$tree_sql = "SELECT * FROM humo_trees WHERE tree_prefix!='EMPTY' ORDER BY tree_order";
		$tree_result = $dbh->query($tree_sql);
		echo '<form method="POST" action="index.php">';
		echo '<input type="hidden" name="page" value="user_notes">';
		echo '<select size="1" name="tree_id" onChange="this.form.submit();">';
			while ($treeDb=$tree_result->fetch(PDO::FETCH_OBJ)){
				$treetext=show_tree_text($treeDb->tree_id, $selected_language);
				$selected='';
				if (isset($tree_id) AND ($treeDb->tree_id==$tree_id)){
					$selected=' SELECTED';
					$note_tree_prefix=$treeDb->tree_prefix; // *** There is note_tree_id, but it's not used at this moment, no value available... ***
					$db_functions->set_tree_id($tree_id);
				}

				$note_qry= "SELECT * FROM humo_user_notes WHERE note_tree_prefix='".$treeDb->tree_prefix."'";
				$note_result = $dbh->query($note_qry);
				$num_rows = $note_result->rowCount();

				echo '<option value="'.$treeDb->tree_id.'"'.$selected.'>'.@$treetext['name'].' ['.$num_rows.']</option>';
			}
		echo '</select>';

		//echo ' <input type="Submit" name="submit_notes" value="'.__('Select').'">';
		echo '</form>';

	echo '</td></tr>';

	if (isset($_POST['note_status']) AND is_numeric($_POST['note_id'])){
		// *** For safety reasons: only save valid values ***
		$note_status='';
		if ($_POST['note_status']=='new'){ $note_status='new'; }
		if ($_POST['note_status']=='approved'){ $note_status='approved'; }
		if ($note_status){
			$sql="UPDATE humo_user_notes
			SET note_status='".$note_status."'
			WHERE note_id='".$_POST['note_id']."'";
			$result=$dbh->query($sql);
		}

		if ($_POST['note_status']=='remove'){
			echo '<div class="confirm">';
				echo __('Are you sure you want to remove this user note?');
			echo ' <form method="post" action="index.php" style="display : inline;">';
			echo '<input type="hidden" name="page" value="user_notes">';
			echo '<input type="hidden" name="tree" value="'.$tree_id.'">';
			echo '<input type="hidden" name="note_id" value="'.$_POST['note_id'].'">';
			echo ' <input type="Submit" name="note_remove" value="'.__('Yes').'" style="color : red; font-weight: bold;">';
			echo ' <input type="Submit" name="submit" value="'.__('No').'" style="color : blue; font-weight: bold;">';
			echo '</form>';
			echo '</div>';
		}
	}

	if (isset($_POST['note_remove']) AND is_numeric($_POST["note_id"])){
		echo '<div class="confirm">';
			// *** Delete source ***
			$sql="DELETE FROM humo_user_notes WHERE note_id='".safe_text_db($_POST["note_id"])."'";
			$result=$dbh->query($sql);
			echo __('User note is removed.');
		echo '</div>';
	}

	// *** Show user added notes ***
	if (isset($note_tree_prefix)){
		$note_qry= "SELECT * FROM humo_user_notes WHERE note_tree_prefix='".$note_tree_prefix."'";
		$note_result = $dbh->query($note_qry);
		$num_rows = $note_result->rowCount();

		echo '<tr class="humo_user_notes"><td>';
			if ($num_rows)
				echo '<a href="#humo_user_notes"></a> ';
			echo __('User notes').'</td><td colspan="2">';
			if ($num_rows)
				printf(__('There are %d user added notes.'), $num_rows);
			else
				printf(__('There are %d user added notes.'), 0);
		echo '</td></tr>';

		while($noteDb=$note_result->fetch(PDO::FETCH_OBJ)){
			$user_qry = "SELECT * FROM humo_users WHERE user_id='".$noteDb->note_user_id."'";
			$user_result = $dbh->query($user_qry);
			$userDb=$user_result->fetch(PDO::FETCH_OBJ);

			echo '<tr class="humo_color"><td>';
				// *** Select status of message ***
				echo '<form method="POST" action="index.php">';
				echo '<input type="hidden" name="page" value="user_notes">';
				echo '<input type="hidden" name="tree" value="'.$tree_id.'">';
				echo '<input type="hidden" name="note_id" value="'.$noteDb->note_id.'">';
				$note_status=''; if ($noteDb->note_status) $note_status=$noteDb->note_status;
				echo '<select size="1" name="note_status">';
					$selected='';
					echo '<option value="new"'.$selected.'>'.__('New').'</option>';
					$selected=''; if ($note_status=='approved') $selected=' SELECTED';
					echo '<option value="approved"'.$selected.'>'.__('Approved').'</option>';
					$selected=''; if ($note_status=='remove') $selected=' SELECTED';
					echo '<option value="remove"'.$selected.'>'.__('Remove').'</option>';
				echo '</select>';

				echo ' <input type="Submit" name="submit_button" value="'.__('Select').'">';
				echo '</form>';
			echo '</td><td>';
				echo '<b>'.$noteDb->note_date.' '.$noteDb->note_time.' '.$userDb->user_name.'</b><br>';
				//echo '<b>'.$noteDb->note_names.'</b><br>';

				// *** Link: index.php?page=editor&amp;tree_id=2_&amp;person=I313 ***
				echo '<b><a href="index.php?page=editor&amp;tree_id='.$tree_id.'&amp;person='.$noteDb->note_pers_gedcomnumber.'">'.$noteDb->note_pers_gedcomnumber.' '.$noteDb->note_names.'</a></b><br>';

				echo nl2br($noteDb->note_note);
			echo '</td></tr>';
		}
	}

echo '</table>';
?>
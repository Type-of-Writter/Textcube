<?php
class Tag {
	/*@static@*/
	function addTagsWithEntryId($owner, $entry, /*string array*/$taglist)
	{
		global $database;
		if ($taglist == null)
			return;
			
		$tmptaglist = array_filter($taglist, 'removeEmptyTagHelper');
		$taglist = array();
		foreach($tmptaglist as $tag) {
			$tag = mysql_real_escape_string(trim($tag));
			array_push($taglist, $tag);
		}

		// step 1. Insert Tags
		$tagliststr = '(\'' . implode('\') , (\'', $taglist) . '\')';
		DBQuery::execute("INSERT IGNORE INTO {$database['prefix']}Tags (name) VALUES $tagliststr ");

		// the point of Race condition
		// if other entry is deleted, some missing tags can be exist so they are not related with this entry.
		
		// step 2. Insert Relations
		$tagliststr =  '\'' . implode('\' , \'', $taglist) . '\'';
		DBQuery::execute("INSERT INTO {$database['prefix']}TagRelations
								(SELECT $owner, t.id, $entry FROM tt_Tags as t 
										WHERE 
											name in ( $tagliststr ) AND  
											t.id NOT IN 
												( SELECT tag FROM tt_TagRelations WHERE 
													(tag = t.id) AND (entry = $entry) AND (owner = $owner)
												)
								)");
	}

	/*@static@*/
	function modifyTagsWithEntryId($owner, $entry, /*string array*/$taglist)
	{
		global $database;
		if ($taglist == null)
			$taglist = array();
			
		$tmptaglist = array_filter($taglist, 'removeEmptyTagHelper');
		$taglist = array();
		foreach($tmptaglist as $tag) {
			$tag = mysql_real_escape_string(trim($tag));
			array_push($taglist, $tag);
		}
		
		// step 1. Get deleted Tag
		$tmpoldtaglist = DBQuery::queryColumn("SELECT name FROM {$database['prefix']}Tags WHERE EXISTS
								(SELECT * FROM {$database['prefix']}TagRelations WHERE owner = $owner AND entry = $entry AND tag = id)");
		if ($tmpoldtaglist == null)
			$tmpoldtaglist = array();
		$oldtaglist = array();
		foreach($tmpoldtaglist as $tag) {
			$tag = mysql_real_escape_string(trim($tag));
			array_push($oldtaglist, $tag);
		}
		
		$deletedTagList = array_diff($oldtaglist, $taglist);
		$insertedTagList = array_diff($taglist, $oldtaglist);
		
		// step 2. Insert Tag
		if (count($insertedTagList) > 0) 
		{
			$tagliststr = '(\'' . implode('\') , (\'', $insertedTagList) . '\')';
			DBQuery::execute("INSERT IGNORE INTO {$database['prefix']}Tags (name) VALUES $tagliststr ");
		
		// step 3. Insert Relation
			$tagliststr =  '\'' . implode('\' , \'', $insertedTagList) . '\'';
			DBQuery::execute("INSERT INTO {$database['prefix']}TagRelations
									(SELECT $owner, t.id, $entry FROM tt_Tags as t 
											WHERE 
												name in ( $tagliststr ) AND  
												t.id NOT IN 
													( SELECT tag FROM tt_TagRelations WHERE 
														(tag = t.id) AND (entry = $entry) AND (owner = $owner)
													)
									)");
		}
		
		// step 4. Delete Tag
		if (count($deletedTagList) > 0)
		{
			// small step, get tag id list
			$tagliststr =  '\'' . implode('\' , \'', $deletedTagList) . '\'';
			$taglist = DBQuery::queryColumn(
					"SELECT id FROM {$database['prefix']}Tags WHERE EXISTS (SELECT * FROM {$database['prefix']}TagRelations 
							WHERE owner = $owner AND entry = $entry AND tag = id)
								AND name in ( $tagliststr )");
			if ($taglist == null) 
				return; // What?
			
			// now delete tag
			$tagliststr = implode(',', $taglist);
			DBQuery::execute("DELETE FROM {$database['prefix']}Tags WHERE id in ( $tagliststr ) AND NOT EXISTS (SELECT * FROM {$database['prefix']}TagRelations WHERE (tag = id) AND ((entry <> $entry) OR (owner <> $owner)))");
		
		// step 5. Delete Relation
			DBQuery::execute("DELETE FROM {$database['prefix']}TagRelations WHERE owner = $owner AND entry = $entry AND tag in ( $tagliststr )");
		
		// step 6. Delete Tag one more time
			DBQuery::execute("DELETE FROM {$database['prefix']}Tags WHERE id in ( $tagliststr ) AND NOT EXISTS (SELECT * FROM {$database['prefix']}TagRelations WHERE (tag = id))");

		}
	}

	/*@static@*/
	function deleteTagsWithEntryId($owner, $entry)
	{
		global $database;
		$taglist = DBQuery::queryColumn("SELECT tag FROM {$database['prefix']}TagRelations WHERE owner = $owner AND entry = $entry");
		if ($taglist != null) {
			$tagliststr = implode(',', $taglist);
			
			DBQuery::execute("DELETE FROM {$database['prefix']}Tags WHERE id in ( $tagliststr ) AND NOT EXISTS (SELECT * FROM {$database['prefix']}TagRelations WHERE (tag = id) AND ((entry <> $entry) OR (owner <> $owner)))");
			DBQuery::execute("DELETE FROM {$database['prefix']}TagRelations WHERE owner = $owner AND entry = $entry");
			DBQuery::execute("DELETE FROM {$database['prefix']}Tags WHERE id in ( $tagliststr ) AND NOT EXISTS (SELECT * FROM {$database['prefix']}TagRelations WHERE (tag = id))");
		}
	}	
}
?>

<?php
/**
* Class ilObjLearningModule
*
* @author Sascha Hofmann <shofmann@databay.de> 
* $Id$
* 
* @extends ilObject
* @package ilias-core
*/

require_once "class.ilObject.php";

class ilObjLearningModule extends ilObject
{
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjLearningModule($a_id,$a_call_by_reference = true)
	{
		$this->type = "le";
		$this->ilObject($a_id,$a_call_by_reference);
	}


	/**
	* if implemented, this function should be called from an Out/GUI-Object
	*/
	function import()
	{
		// nothing to do. just display the dialogue in Out
		return;
	}


	/**
	* create new learning module object
	*
	*/
	function putInTree($a_parent)
	{
		global $tree;

		// put this object in tree under $a_parent
		parent::putInTree($a_parent);

		// make new tree for this object
		$tree->addTree($this->getId());
	}



	/**
	* uploads a complete LearningModule from a LO-XML file
	*
	* @access	public
	*/
	function upload($a_parse_mode, $a_file, $a_name)
	{
		require_once "classes/class.xml2sql.php";
		require_once "classes/class.ilDOMXML.php";

		$source = $a_file;

		// create domxml-handler
		$domxml = new ilDOMXML();

		//get XML-file, parse and/or validate the document
		$file = $a_name;
		$root = $domxml->loadDocument(basename($source),dirname($source),$a_parse_mode);

		// remove empty text nodes
		$domxml->trimDocument();

		$n = 0;

		// Identify Leaf-LOS (LOs not containing other LOs)
		while (count($elements = $domxml->getElementsByTagname("LearningObject")) > 1)
		{
			// delete first element since this is always the root LearningObject
			array_shift($elements);

			foreach ($elements as $element)
			{
				if ($domxml->isLeafElement($element,"LearningObject",1))
				{
					$n++;

					$leaf_elements[] = $element;

					// copy whole LearningObject to $subtree
					$subtree = $element->clone_node(true);

					$prev_sibling = $element->previous_sibling();
					$parent = $element->parent_node();

					// remove the LearningObject from main file
					$element->unlink_node();

					// create a new domDocument containing the isolated LearningObject in $subtree
					$lo = new ilDOMXML();
					$node  = $lo->appendChild($subtree);

					// get LO informationen (title & description)
					$obj_data = $lo->getInfo();

					// get unique obj_id of LO
					require_once "classes/class.ilObjLearningObject.php";
					$loObj = new ilObjLearningObject();
					$loObj->setTitle($obj_data["title"]);
					$loObj->setDescription($obj_data["desc"]);
					$loObj->create();
					$lo_id = $loObj->getId();
					unset($loObj);

					// prepare LO for database insertion
					$lotree = $lo->buildTree();

					// create a reference in main file with global obj_id of inserted LO
					// DIRTY: save lm_id too for xsl linking
					$domxml->appendReferenceNodeForLO ($parent,$lo_id,$this->id,$prev_sibling);

					// write to file
//					$lo->domxml->doc->dump_file("c:/htdocs/ilias3/test2/".$lo_id.".xml");
					//echo "<b>LearningObject ".$n."</b><br/>";
					//echo "<pre>".htmlentities($lo->domxml->dumpDocument())."</pre>";

					// insert LO into lo_database
					$xml2sql = new xml2sql($lotree,$lo_id);
					$xml2sql->insertDocument();

					//fetch internal element id, parent_id and save them to reconstruct tree later on
					$mapping[] = array ($lo_id => $lo->getReferences());
				}
			}
		} // END: while. Continue until only the root LO is left in main file

		$n++;

		// write root LO to file (TESTING)
//		$domxml->doc->dump_file("c:/htdocs/ilias3/test2/root.xml");
		//echo "<b>LearningObject ".$n."</b><br/>";
		//echo "<pre>".htmlentities($domxml->dumpDocument())."</pre>";

		// insert the remaining root-LO into DB
		$lo = new ilDOMXML($domxml->doc);
		$obj_data = $lo->getInfo();

		require_once "classes/class.ilObjLearningObject.php";
		$loObj = new ilObjLearningObject();
		$loObj->setTitle($obj_data["title"]);
		$loObj->setDescription($obj_data["desc"]);
		$loObj->create();
		$lo_id = $loObj->getId();
		unset($loObj);

		$lotree = $lo->buildTree();
		$xml2sql = new xml2sql($lotree,$lo_id);
		$xml2sql->insertDocument();

		// copying file to server if document is valid (soon...)
		//move_uploaded_file($a_source,$path()."/".$a_obj_id."_".$a_name);
		$last[$lo_id] = $lo->getReferences();
		array_push($mapping,$last);

		// MOVE TO xml2sql class
		$xml2sql->insertStructureIntoTree(array_reverse($mapping),$this->id);

		// for output
		return $data;
	}
} // END class.LearningModuleObject
?>

<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Workspace deep link handler GUI
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 * 
 * @ilCtrl_Calls ilSharedResourceGUI: ilObjBlogGUI, ilObjFileGUI
 *
 * @ingroup ServicesPersonalWorkspace
 */
class ilSharedResourceGUI
{
	protected $node_id;
	protected $access_handler;	

	function __construct()
	{
		global $ilCtrl;
		
		$ilCtrl->saveParameter($this, "wsp_id");
		$this->node_id = $_GET["wsp_id"];			
	}
	
	function executeCommand()
	{
		global $ilCtrl, $tpl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd();
	
		$tpl->getStandardTemplate();
		
		switch($next_class)
		{
			case "ilobjbloggui":
				include_once "Modules/Blog/classes/class.ilObjBlogGUI.php";
				$bgui = new ilObjBlogGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($bgui);			
				break;
			
			case "ilobjfilegui":
				include_once "Modules/File/classes/class.ilObjFileGUI.php";
				$fgui = new ilObjFileGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($fgui);
				break;				
			
			default:
				if(!$cmd)
				{
					$cmd = "process";
				}
				$this->$cmd();
		}
		
		$tpl->show();
	}
	
	protected function process()
	{
		global $ilUser, $ilCtrl;
		
		if(!$this->node_id)
		{
			exit("invalid call");
		}
		
		$has_permission = false;
		
		// if we have current user - check with normal access handler
		if($ilUser && $ilUser->getId())
		{
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
			
			$tree = new ilWorkspaceTree($ilUser->getId());
			$access_handler = new ilWorkspaceAccessHandler($tree);
			if($access_handler->checkAccess("read", "", $this->node_id))
			{
				$has_permission = true;
			}
		}
	
		// check if public (no proper login needed)
		if(!$has_permission)
		{
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessGUI.php";
			
			$shared = ilWorkspaceAccessHandler::getPermissions($this->node_id);
		
			// object is "public"
			if(in_array(ilWorkspaceAccessGUI::PERMISSION_ALL, $shared))
			{
				$has_permission = true;			
			}

			// password protected
			if(!$has_permission && in_array(ilWorkspaceAccessGUI::PERMISSION_ALL_PASSWORD, $shared))
			{
				$ilCtrl->redirect($this, "passwordForm");
			}			
		}		
				
		if(!$has_permission)
		{
			if(!$ilUser)		
			{
				// registered or user-related
				var_dump(":TODO: redirect to login first");
				exit();
			}
			else
			{
				exit("no permission");
			}
		}
		
		$this->redirectToResource($this->node_id);	     		
	}
	
	protected function redirectToResource($a_node_id)
	{
		global $ilCtrl;
				
		$object_data = $this->getObjectDataFromNode($a_node_id);

		if(!$object_data["obj_id"])
		{
			exit("invalid object");
		}
		
		switch($object_data["type"])
		{
			case "blog":
			case "file":
				$ilCtrl->setParameterByClass("ilobj".$object_data["type"]."gui", "wsp_id", $a_node_id);
				$ilCtrl->redirectByClass("ilobj".$object_data["type"]."gui");
		
			default:
				exit("invalid object type");						
		}		
	}
	
	protected function getObjectDataFromNode($a_node_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT obj.obj_id, obj.type, obj.title".
			" FROM object_reference_ws ref".
			" JOIN tree_workspace tree ON (tree.child = ref.wsp_id)".
			" JOIN object_data obj ON (ref.obj_id = obj.obj_id)".
			" WHERE ref.wsp_id = ".$ilDB->quote($a_node_id, "integer"));
		return $ilDB->fetchAssoc($set);
	}
	
	protected function passwordForm($form = null)
	{
		global $tpl, $lng;
		
		$lng->loadLanguageModule("wsp");
		
		$tpl->setTitle($lng->txt("wsp_password_protected_resource"));
		$tpl->setDescription($lng->txt("wsp_password_protected_resource_info"));
		
		if(!$form)
		{
			$form = $this->initPasswordForm();
		}
	
		$tpl->setContent($form->getHTML());		
	}
	
	protected function initPasswordForm()
	{
		global $ilCtrl, $lng;
		
		$object_data = $this->getObjectDataFromNode($this->node_id);
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($lng->txt("wsp_password_for").": ".$object_data["title"]);
		
		$password = new ilTextInputGUI($lng->txt("password"), "password");
		$password->setRequired(true);
		$form->addItem($password);
		
		$form->addCommandButton('checkPassword', $lng->txt("submit"));
		
		return $form;
	}
	
	protected function checkPassword()
	{
		global $ilDB, $lng;
		 
		$form = $this->initPasswordForm();
		if($form->checkInput())
		{
			$input = md5($form->getInput("password"));
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";
			$password = ilWorkspaceAccessHandler::getSharedNodePassword($this->node_id);
			if($input == $password)
			{
				ilWorkspaceAccessHandler::keepSharedSessionPassword($this->node_id, $input);				
				$this->redirectToResource($this->node_id);
			}
			else
			{
				$item = $form->getItemByPostVar("password");
				$item->setAlert($lng->txt("wsp_invalid_password"));
				ilUtil::sendFailure($lng->txt("form_input_not_valid"));
			}						
		}		
		
		$form->setValuesByPost();
		$this->passwordForm($form);
	}
}

?>
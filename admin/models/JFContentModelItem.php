<?php
/**
 * Joom!Fish - Multi Lingual extention and translation manager for Joomla!
 * Copyright (C) 2003 - 2013, Think Network GmbH, Konstanz
 *
 * All rights reserved.  The Joom!Fish project is a set of extentions for
 * the content management system Joomla!. It enables Joomla!
 * to manage multi lingual sites especially in all dynamic information
 * which are stored in the database.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * @package joomfish
 * @subpackage Models
 *
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

include_once(JPATH_ADMINISTRATOR."/components/com_content/models/article.php");


class JFContentModelItem  extends ContentModelArticle {
	
	
	
	public function __construct($config = array())
	{
		// Must set option value to override constructors attempts to find it!
		$this->option  = "com_content";
		return parent::__construct($config);
	}
	
	/**
	 * Overload Method to get a form object - we MUST NOT use JPATH_COMPONENT
	 *
	 * @param   string   $name     The name of the form.
	 * @param   string   $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array    $options  Optional array of options for the form creation.
	 * @param   boolean  $clear    Optional argument to force load a new form.
	 * @param   string   $xpath    An optional xpath to search for the fields.
	 *
	 * @return  mixed  JForm object on success, False on error.
	 *
	 * @see     JForm
	 * @since   11.1
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control']	= JArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source.serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear) {
			return $this->_forms[$hash];
		}

		// Get the form.
		if (strpos($name, "com_")===0){
			if (strpos($name , ".")>0){
				$component = substr($name, 0, strpos($name , "."));
			}
			else {
				$component = $name;
			}
			$componentpath = JPATH_BASE."/components/".$component;
			JForm::addFormPath($componentpath.'/models/forms');
			JForm::addFieldPath($componentpath.'/models/fields');			
		}
		else {
			JForm::addFormPath(JPATH_COMPONENT.'/models/forms');
			JForm::addFieldPath(JPATH_COMPONENT.'/models/fields');
		}

		try {
			$form = JForm::getInstance($name, $source, $options, false, $xpath);

			if (isset($options['load_data']) && $options['load_data']) {
				// Get the data for the form.
				$data = $this->loadFormData();
			} else {
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);

		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}
	

	public function getItem($translation=null)
	{
		$item = parent::getItem();
		return $item;
		
		$table = clone(parent::getItem());

		// I could pick up the URL here or treat as a special content element field type?
		if ($table->type == 'component'){

			// Note that to populate the initial value of the urlparams
			$conf = JFactory::getConfig();
			$elementTable = $conf->getValue('joomfish.elementTable',false);
			foreach ($elementTable->Fields as $efield) {
				if ($efield->Name=="link" && isset($efield->translationContent->value) && $efield->translationContent->value!==""){
					$uri = new JURI($efield->translationContent->value);
					if ($uri->getVar("option",false)){
						$table->link = $efield->translationContent->value;
					}
				}
			}

			$url = str_replace('index.php?', '', $table->link);
			$url = str_replace('&amp;', '&', $url);
			$table->linkparts = null;
			if(strpos($url, '&amp;') !== false)
			{
			   $url = str_replace('&amp;','&',$url);
			}
			
			parse_str($url, $table->linkparts);

			$db = $this->getDBO();
			if ($component = @$table->linkparts['option']) {
				$query = 'SELECT `extension_id`' .
				' FROM `#__extensions`' .
				' WHERE `element` = "'.$db->getEscaped($component).'"';
				$db->setQuery( $query );
				$table->componentid = $db->loadResult();
			}
		}
		$item = $table;
		return $item;
	}

	
}
/*class JFDefaultContentModelItem extends JFTempContentModelItem {

	function &getItem()
	{

		$table =  parent::getItem();
		$clone = clone($table);
		// get an empty version for the defalut
		JRequest::setVar("edit",false);
		$table = null;
		JRequest::setVar( 'cid',array(0));
		$table =  parent::getItem();
		$item = clone($table);
		$item->component_id = $clone->component_id;
		$item->type = $clone->type;

		//$component		= $this->getComponent();

		// restore original
		$table = $clone;

		return $item;
	}

}*/
?>

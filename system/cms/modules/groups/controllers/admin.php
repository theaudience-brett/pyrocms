<?php

use Pyro\Module\Groups\Model\Group;

/**
 * Roles controller for the groups module
 *
 * @author		Phil Sturgeon
 * @author		PyroCMS Dev Team
 * @package	 PyroCMS\Core\Modules\Groups\Controllers
 *
 */
class Admin extends Admin_Controller
{

	/**
	 * Constructor method
	 */
	public function __construct()
	{
		parent::__construct();

		// Load the required classes
		$this->load->library('form_validation');

		$this->lang->load('group');
		$this->lang->load('permissions/permissions');

		// Validation rules
		$this->validation_rules = array(
			array(
				'field' => 'name',
				'label' => lang('groups:name'),
				'rules' => 'trim|required|max_length[100]'
			),
			array(
				'field' => 'description',
				'label' => lang('groups:description'),
				'rules' => 'trim|required|max_length[250]'
			)
		);
	}

	/**
	 * Create a new group role
	 */
	public function index()
	{
		$groups = Group::all();

		$this->template
			->title($this->module_details['name'])
			->set('groups', $groups)
			->build('admin/index');
	}

	/**
	 * Create a new group role
	 */
	public function add()
	{
		$group = new Group;

		if ($_POST)
		{
			$this->form_validation->set_rules($this->validation_rules);

			if ($this->form_validation->run())
			{
				if ($group->create(array(
						'name' => $this->input->post('name'),
						'description' => $this->input->post('description')
					)))
				{
					// Fire an event. A new group has been created.
					Events::trigger('group_created', $group);

					$this->session->set_flashdata('success', sprintf(lang('groups:add_success'), $group->name));
				}
				else
				{
					$this->session->set_flashdata('error', sprintf(lang('groups:add_error'), $group->name));
				}

				redirect('admin/groups');
			}
		}

		// Loop through each validation rule
		foreach ($this->validation_rules as $rule)
		{
			$group->{$rule['field']} = set_value($rule['field']);
		}
		
		$this->template
			->title($this->module_details['name'], lang('groups:add_title'))
			->set('group', $group)
			->build('admin/form');
	}


	/**
	 * Edit a group role
	 *
	 * @param int $id The id of the group.
	 */
	public function edit($id = 0)
	{
		$group = Group::find($id);

		// Make sure we found something
		$group or redirect('admin/groups');

		if ($_POST)
		{
			// Got validation?
			if ($group->name == 'admin' or $group->name == 'user')
			{
				//if they're changing description on admin or user save the old name
				$_POST['name'] = $group->name;
				$this->form_validation->set_rules('description', lang('groups:description'), 'trim|required|max_length[250]');
			}
			else
			{
				$this->form_validation->set_rules($this->validation_rules);
			}

			if ($this->form_validation->run())
			{	
				$group->name = $this->input->post('name');
				$group->description = $this->input->post('description');
				
				if ($success = $group->save())
				{
					// Fire an event. A group has been updated.
					Events::trigger('group_updated', $group);
					$this->session->set_flashdata('success', sprintf(lang('groups:edit_success'), $group->name));
				}
				else
				{
					$this->session->set_flashdata('error', sprintf(lang('groups:edit_error'), $group->name));
				}

				redirect('admin/groups');
			}
		}

		$this->template
			->title($this->module_details['name'], sprintf(lang('groups:edit_title'), $group->name))
			->set('group', $group)
			->build('admin/form');
	}

	/**
	 * Delete group role(s)
	 *
	 * @param int $id The id of the group.
	 */
	public function delete($id = 0)
	{
		if ($success = Group::find($id)->delete())
		{
			// Fire an event. A group has been deleted.
			Events::trigger('group_deleted', $id);

			$this->session->set_flashdata('success', lang('groups:delete_success'));
		}
		else
		{
			$this->session->set_flashdata('error', lang('groups:delete_error'));
		}

		redirect('admin/groups');
	}
}

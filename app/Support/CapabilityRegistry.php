<?php

namespace App\Support;

class CapabilityRegistry {
	public static function all(): array {
		return [


			/*
			|--------------------------------------------------------------------------
			| Users
			|--------------------------------------------------------------------------
			*/
			'users' => [
				['name' => 'users.view', 'label' => 'View Users'],
				['name' => 'users.create', 'label' => 'Create User'],
				['name' => 'users.update', 'label' => 'Update User'],
				['name' => 'users.delete', 'label' => 'Delete User'],
				['name' => 'users.activate', 'label' => 'Activate / Deactivate User'],
				['name' => 'users.profile.view', 'label' => 'View Profile'],
				['name' => 'users.profile.update', 'label' => 'Update Own Profile'],
				['name' => 'users.password.change', 'label' => 'Change Own Password'],
				['name' => 'users.role.assign', 'label' => 'Assign Role'],
			],

			/*
			|--------------------------------------------------------------------------
			| Roles & Permissions
			|--------------------------------------------------------------------------
			*/
			'roles' => [
				['name' => 'roles.view', 'label' => 'View Roles'],
				['name' => 'roles.create', 'label' => 'Create Role'],
				['name' => 'roles.update', 'label' => 'Update Role'],
				['name' => 'roles.delete', 'label' => 'Delete Role'],
				['name' => 'permissions.view', 'label' => 'View Permissions'],
			],

			/*
			|--------------------------------------------------------------------------
			| Projects
			|--------------------------------------------------------------------------
			*/
			'projects' => [
				['name' => 'projects.view', 'label' => 'View Projects'],
				['name' => 'projects.create', 'label' => 'Create Project'],
				['name' => 'projects.update', 'label' => 'Update Project'],
				['name' => 'projects.delete', 'label' => 'Delete Project'],
				['name' => 'projects.archive', 'label' => 'Archive Project'],
				['name' => 'projects.restore', 'label' => 'Restore Archived Project'],
				['name' => 'projects.members.manage', 'label' => 'Manage Project Members'],
			],

			/*
			|--------------------------------------------------------------------------
			| Tasks
			|--------------------------------------------------------------------------
			*/
			'tasks' => [
				['name' => 'tasks.view', 'label' => 'View Tasks'],
				['name' => 'tasks.create', 'label' => 'Create Task'],
				['name' => 'tasks.update', 'label' => 'Update Task'],
				['name' => 'tasks.delete', 'label' => 'Delete Task'],
				['name' => 'tasks.archive', 'label' => 'Archive Task'],
				['name' => 'tasks.restore', 'label' => 'Restore Task'],
				['name' => 'tasks.assign', 'label' => 'Assign Users to Task'],
				['name' => 'tasks.status.update', 'label' => 'Update Task Status'],
				['name' => 'tasks.priority.update', 'label' => 'Update Task Priority'],
				['name' => 'tasks.deadline.update', 'label' => 'Update Task Deadline'],
				['name' => 'tasks.subtask.manage', 'label' => 'Manage Subtasks'],
				['name' => 'tasks.dependency.manage', 'label' => 'Manage Dependencies'],
				['name' => 'tasks.reorder', 'label' => 'Reorder Tasks'],
				['name' => 'tasks.bulk.update', 'label' => 'Bulk Update Tasks'],
			],

			/*
			|--------------------------------------------------------------------------
			| Comments & Collaboration
			|--------------------------------------------------------------------------
			*/
			'comments' => [
				['name' => 'comments.view', 'label' => 'View Comments'],
				['name' => 'comments.create', 'label' => 'Add Comment'],
				['name' => 'comments.update', 'label' => 'Edit Comment'],
				['name' => 'comments.delete', 'label' => 'Delete Comment'],
				['name' => 'comments.mention', 'label' => 'Mention Users'],
				['name' => 'comments.react', 'label' => 'React to Comment'],
			],

			/*
			|--------------------------------------------------------------------------
			| Files
			|--------------------------------------------------------------------------
			*/
			'files' => [
				['name' => 'files.view', 'label' => 'View Files'],
				['name' => 'files.upload', 'label' => 'Upload File'],
				['name' => 'files.delete', 'label' => 'Delete File'],
				['name' => 'files.version.manage', 'label' => 'Manage File Versions'],
			],

			/*
			|--------------------------------------------------------------------------
			| Time Tracking
			|--------------------------------------------------------------------------
			*/
			'time' => [
				['name' => 'time.start', 'label' => 'Start Timer'],
				['name' => 'time.stop', 'label' => 'Stop Timer'],
				['name' => 'time.manual.entry', 'label' => 'Manual Time Entry'],
				['name' => 'time.view.own', 'label' => 'View Own Time Entries'],
				['name' => 'time.view.all', 'label' => 'View All Time Entries'],
				['name' => 'time.export', 'label' => 'Export Time Report'],
				['name' => 'time.approve', 'label' => 'Approve Timesheet'],
			],

			/*
			|--------------------------------------------------------------------------
			| Notifications
			|--------------------------------------------------------------------------
			*/
			'notifications' => [
				['name' => 'notifications.view', 'label' => 'View Notifications'],
				['name' => 'notifications.mark.read', 'label' => 'Mark Notification as Read'],
				['name' => 'notifications.settings.manage', 'label' => 'Manage Notification Settings'],
			],

			/*
			|--------------------------------------------------------------------------
			| Activity / Audit
			|--------------------------------------------------------------------------
			*/
			'activity' => [
				['name' => 'activity.view', 'label' => 'View Activity Log'],
				['name' => 'activity.export', 'label' => 'Export Activity Log'],
			],

			/*
			|--------------------------------------------------------------------------
			| Reports & Analytics
			|--------------------------------------------------------------------------
			*/
			'reports' => [
				['name' => 'reports.view', 'label' => 'View Reports'],
				['name' => 'reports.project', 'label' => 'Project Progress Report'],
				['name' => 'reports.task', 'label' => 'Task Completion Report'],
				['name' => 'reports.user', 'label' => 'User Performance Report'],
				['name' => 'reports.time', 'label' => 'Time Tracking Report'],
				['name' => 'reports.export.pdf', 'label' => 'Export PDF'],
				['name' => 'reports.export.excel', 'label' => 'Export Excel'],
			],

			/*
			|--------------------------------------------------------------------------
			| Clients
			|--------------------------------------------------------------------------
			*/
			'clients' => [
				['name' => 'clients.view', 'label' => 'View Clients'],
				['name' => 'clients.create', 'label' => 'Create Client'],
				['name' => 'clients.update', 'label' => 'Update Client'],
				['name' => 'clients.delete', 'label' => 'Delete Client'],
				['name' => 'clients.project.view', 'label' => 'Client View Project'],
			],

			/*
			|--------------------------------------------------------------------------
			| Search
			|--------------------------------------------------------------------------
			*/
			'search' => [
				['name' => 'search.global', 'label' => 'Global Search'],
				['name' => 'search.saved.filter.manage', 'label' => 'Manage Saved Filters'],
			],

			/*
			|--------------------------------------------------------------------------
			| Settings
			|--------------------------------------------------------------------------
			*/
			'settings' => [
				['name' => 'settings.view', 'label' => 'View Settings'],
				['name' => 'settings.update', 'label' => 'Update Settings'],
				['name' => 'settings.branding.manage', 'label' => 'Manage Branding'],
				['name' => 'settings.email.manage', 'label' => 'Manage Email Settings'],
				['name' => 'settings.language.manage', 'label' => 'Manage Language'],
			],

			/*
			|--------------------------------------------------------------------------
			| System / Technical
			|--------------------------------------------------------------------------
			*/
			'system' => [
				['name' => 'system.api.access', 'label' => 'Access API'],
				['name' => 'system.webhook.manage', 'label' => 'Manage Webhooks'],
				['name' => 'system.backup.manage', 'label' => 'Manage System Backup'],
				['name' => 'system.cache.clear', 'label' => 'Clear System Cache'],
			],

		];
	}
}

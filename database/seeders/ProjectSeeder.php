<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder {
    /**
     * Seed projects with tasks, labels, subtasks, and related data.
     * Users are seeded by UserSeeder - this seeder uses existing users.
     */
    public function run(): void {
        // Get users by email (seeded by UserSeeder)
        $users = $this->getUsers();

        // Create projects
        $projects = $this->seedProjects($users);

        // Create labels for each project
        $labels = $this->seedLabels($projects);

        // Create tasks with assignees, labels, and subtasks
        $this->seedTasks($users, $projects, $labels);

        $this->command->info('✅ Projects, tasks, labels, and subtasks seeded successfully.');
    }

    private function getUsers(): array {
        return [
            'rupash'   => User::where('email', 'rupash.das.202@gmail.com')->first(),
            'debos'    => User::where('email', 'debos.das.02@gmail.com')->first(),
            'nishan'   => User::where('email', 'nishandas880@gmail.com')->first(),
            'tanjim'   => User::where('email', 'tanjimahmmed@gmail.com')->first(),
            'prottasha' => User::where('email', 'prottasha@gmail.com')->first(),
        ];
    }

    private function seedProjects(array $users): array {
        $projectsData = [
            [
                'name' => 'Website Redesign',
                'description' => 'Complete overhaul of the company website with modern design and improved UX.',
                'goal' => 'Increase conversion rate by 30% and improve page load times.',
                'status' => 'In Progress',
                'priority' => 'High',
                'color' => 'bg-violet-500',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'progress' => 45,
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Native mobile application for iOS and Android platforms.',
                'goal' => 'Launch MVP with core features within 3 months.',
                'status' => 'Planning',
                'priority' => 'Urgent',
                'color' => 'bg-emerald-500',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(105),
                'progress' => 10,
            ],
            [
                'name' => 'API Documentation',
                'description' => 'Comprehensive API documentation with examples and interactive guides.',
                'goal' => 'Reduce support tickets by 50% through self-service documentation.',
                'status' => 'In Progress',
                'priority' => 'Medium',
                'color' => 'bg-sky-500',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(40),
                'progress' => 60,
            ],
            [
                'name' => 'Legacy System Migration',
                'description' => 'Migrate legacy Perl codebase to modern Python microservices architecture.',
                'goal' => 'Reduce infrastructure costs by 40% and improve scalability.',
                'status' => 'On Hold',
                'priority' => 'High',
                'color' => 'bg-amber-500',
                'start_date' => now()->subDays(60),
                'end_date' => now()->addDays(180),
                'progress' => 25,
            ],
            [
                'name' => 'E-Commerce Platform',
                'description' => 'Build a full-featured e-commerce platform with payment integration.',
                'goal' => 'Process 1000 orders per day with 99.9% uptime.',
                'status' => 'Planning',
                'priority' => 'Urgent',
                'color' => 'bg-rose-500',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(150),
                'progress' => 5,
            ],
            [
                'name' => 'Customer Portal',
                'description' => 'Self-service portal for customers to manage their accounts and orders.',
                'goal' => 'Reduce customer support calls by 40%.',
                'status' => 'In Progress',
                'priority' => 'High',
                'color' => 'bg-fuchsia-500',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(75),
                'progress' => 35,
            ],
            [
                'name' => 'Analytics Dashboard',
                'description' => 'Real-time analytics dashboard with interactive charts and reports.',
                'goal' => 'Provide actionable insights within 5 seconds of data entry.',
                'status' => 'In Progress',
                'priority' => 'Medium',
                'color' => 'bg-indigo-500',
                'start_date' => now()->subDays(20),
                'end_date' => now()->addDays(50),
                'progress' => 55,
            ],
            [
                'name' => 'Email Marketing Tool',
                'description' => 'Automated email marketing campaign management system.',
                'goal' => 'Increase email open rates by 25%.',
                'status' => 'Completed',
                'priority' => 'Medium',
                'color' => 'bg-teal-500',
                'start_date' => now()->subDays(90),
                'end_date' => now()->subDays(10),
                'progress' => 100,
            ],
            [
                'name' => 'Security Audit',
                'description' => 'Comprehensive security audit and penetration testing.',
                'goal' => 'Identify and fix all critical vulnerabilities.',
                'status' => 'In Progress',
                'priority' => 'Urgent',
                'color' => 'bg-red-500',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'progress' => 30,
            ],
            [
                'name' => 'Performance Optimization',
                'description' => 'Optimize database queries and caching strategies.',
                'goal' => 'Reduce average response time to under 200ms.',
                'status' => 'Completed',
                'priority' => 'High',
                'color' => 'bg-orange-500',
                'start_date' => now()->subDays(45),
                'end_date' => now()->subDays(5),
                'progress' => 100,
            ],
            [
                'name' => 'Payment Gateway Integration',
                'description' => 'Integrate multiple payment gateways including Stripe and PayPal.',
                'goal' => 'Support 5 payment methods with seamless checkout.',
                'status' => 'In Progress',
                'priority' => 'High',
                'color' => 'bg-pink-500',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(35),
                'progress' => 50,
            ],
            [
                'name' => 'Inventory Management',
                'description' => 'Real-time inventory tracking and management system.',
                'goal' => 'Eliminate stockouts and reduce overstock by 30%.',
                'status' => 'Planning',
                'priority' => 'Medium',
                'color' => 'bg-cyan-500',
                'start_date' => now()->addDays(20),
                'end_date' => now()->addDays(120),
                'progress' => 0,
            ],
            [
                'name' => 'CRM Implementation',
                'description' => 'Customer relationship management system deployment.',
                'goal' => 'Improve customer retention rate by 20%.',
                'status' => 'In Progress',
                'priority' => 'High',
                'color' => 'bg-lime-500',
                'start_date' => now()->subDays(25),
                'end_date' => now()->addDays(55),
                'progress' => 65,
            ],
            [
                'name' => 'Social Media Integration',
                'description' => 'Integrate social media sharing and authentication.',
                'goal' => 'Increase signups via social login by 50%.',
                'status' => 'Completed',
                'priority' => 'Low',
                'color' => 'bg-blue-500',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(20),
                'progress' => 100,
            ],
            [
                'name' => 'Search Engine Optimization',
                'description' => 'SEO improvements for better organic search rankings.',
                'goal' => 'Achieve top 3 ranking for 10 key terms.',
                'status' => 'In Progress',
                'priority' => 'Medium',
                'color' => 'bg-green-500',
                'start_date' => now()->subDays(7),
                'end_date' => now()->addDays(63),
                'progress' => 40,
            ],
            [
                'name' => 'Mobile Push Notifications',
                'description' => 'Implement push notification system for mobile app.',
                'goal' => 'Achieve 60% notification open rate.',
                'status' => 'Planning',
                'priority' => 'Medium',
                'color' => 'bg-yellow-500',
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(70),
                'progress' => 0,
            ],
            [
                'name' => 'User Feedback System',
                'description' => 'In-app feedback collection and management system.',
                'goal' => 'Collect and respond to feedback within 24 hours.',
                'status' => 'In Progress',
                'priority' => 'Low',
                'color' => 'bg-purple-500',
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(27),
                'progress' => 70,
            ],
            [
                'name' => 'Data Warehouse Setup',
                'description' => 'Build data warehouse for business intelligence.',
                'goal' => 'Enable self-service analytics for all departments.',
                'status' => 'On Hold',
                'priority' => 'High',
                'color' => 'bg-gray-500',
                'start_date' => now()->subDays(40),
                'end_date' => now()->addDays(140),
                'progress' => 15,
            ],
            [
                'name' => 'Multi-Tenant Architecture',
                'description' => 'Convert application to support multiple tenants.',
                'goal' => 'Serve 100 tenants on a single infrastructure.',
                'status' => 'Planning',
                'priority' => 'Urgent',
                'color' => 'bg-cool-500',
                'start_date' => now()->addDays(45),
                'end_date' => now()->addDays(165),
                'progress' => 0,
            ],
            [
                'name' => 'Accessibility Compliance',
                'description' => 'WCAG 2.1 AA compliance audit and remediation.',
                'goal' => 'Achieve full WCAG 2.1 AA compliance.',
                'status' => 'In Progress',
                'priority' => 'High',
                'color' => 'bg-warm-500',
                'start_date' => now()->subDays(12),
                'end_date' => now()->addDays(48),
                'progress' => 25,
            ],
            [
                'name' => 'Internationalization',
                'description' => 'Add support for multiple languages and locales.',
                'goal' => 'Support 10 languages with localized content.',
                'status' => 'Planning',
                'priority' => 'Medium',
                'color' => 'bg-hot-500',
                'start_date' => now()->addDays(60),
                'end_date' => now()->addDays(200),
                'progress' => 0,
            ],
        ];

        $projects = [];

        foreach ($projectsData as $index => $data) {
            $project = Project::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'created_by' => $users['rupash']->id,
                ])
            );
            $projects[] = $project;

            // Add project creator as owner
            $project->members()->syncWithoutDetaching([
                $users['rupash']->id => ['role' => 'Owner'],
            ]);

            // Add team members based on project type (cycling through available developers)
            $teamAssignments = [
                $users['tanjim']->id => ['role' => 'Lead Developer'],
                $users['debos']->id => ['role' => 'Backend Developer'],
                $users['nishan']->id => ['role' => 'Frontend Developer'],
                $users['prottasha']->id => ['role' => 'Project Manager'],
            ];

            // Add 2-4 team members per project based on index
            $memberIndices = match (true) {
                $index % 5 === 0 => [0, 1, 2], // 3 members
                $index % 5 === 1 => [0, 1],     // 2 members
                $index % 5 === 2 => [0, 2, 3], // 3 members
                $index % 5 === 3 => [1, 2],     // 2 members
                $index % 5 === 4 => [0, 1, 2, 3], // 4 members
            };

            foreach ($memberIndices as $mi) {
                $userId = array_keys($teamAssignments)[$mi];
                $project->members()->syncWithoutDetaching([
                    $userId => $teamAssignments[$userId],
                ]);
            }
        }

        $this->command->info("✅ Created " . count($projects) . " projects with members.");
        return $projects;
    }

    private function seedLabels(array $projects): array {
        $labelsData = [
            // Project 0: Website Redesign
            ['project_index' => 0, 'name' => 'Bug', 'color' => '#EF4444'],
            ['project_index' => 0, 'name' => 'Feature', 'color' => '#10B981'],
            ['project_index' => 0, 'name' => 'Enhancement', 'color' => '#3B82F6'],
            ['project_index' => 0, 'name' => 'Design', 'color' => '#8B5CF6'],
            ['project_index' => 0, 'name' => 'Content', 'color' => '#F59E0B'],
            // Project 1: Mobile App
            ['project_index' => 1, 'name' => 'iOS', 'color' => '#3B82F6'],
            ['project_index' => 1, 'name' => 'Android', 'color' => '#10B981'],
            ['project_index' => 1, 'name' => 'Backend', 'color' => '#6366F1'],
            ['project_index' => 1, 'name' => 'UI/UX', 'color' => '#EC4899'],
            ['project_index' => 1, 'name' => 'Performance', 'color' => '#F59E0B'],
            // Project 2: API Documentation
            ['project_index' => 2, 'name' => 'Authentication', 'color' => '#6366F1'],
            ['project_index' => 2, 'name' => 'Endpoints', 'color' => '#10B981'],
            ['project_index' => 2, 'name' => 'Examples', 'color' => '#F59E0B'],
            ['project_index' => 2, 'name' => 'Errors', 'color' => '#EF4444'],
            // Project 3: Legacy Migration
            ['project_index' => 3, 'name' => 'Backend', 'color' => '#6366F1'],
            ['project_index' => 3, 'name' => 'Database', 'color' => '#10B981'],
            ['project_index' => 3, 'name' => 'Testing', 'color' => '#EC4899'],
            ['project_index' => 3, 'name' => 'Deployment', 'color' => '#8B5CF6'],
            // Project 4: E-Commerce Platform
            ['project_index' => 4, 'name' => 'Payment', 'color' => '#6366F1'],
            ['project_index' => 4, 'name' => 'Frontend', 'color' => '#10B981'],
            ['project_index' => 4, 'name' => 'Backend', 'color' => '#F59E0B'],
            // Project 5: Customer Portal
            ['project_index' => 5, 'name' => 'UI', 'color' => '#EC4899'],
            ['project_index' => 5, 'name' => 'API', 'color' => '#3B82F6'],
            ['project_index' => 5, 'name' => 'Integration', 'color' => '#10B981'],
            // Project 6: Analytics Dashboard
            ['project_index' => 6, 'name' => 'Charts', 'color' => '#8B5CF6'],
            ['project_index' => 6, 'name' => 'Data', 'color' => '#10B981'],
            ['project_index' => 6, 'name' => 'Backend', 'color' => '#F59E0B'],
            // Project 7: Email Marketing (completed, no labels needed)
            // Project 8: Security Audit
            ['project_index' => 8, 'name' => 'Critical', 'color' => '#EF4444'],
            ['project_index' => 8, 'name' => 'Medium', 'color' => '#F59E0B'],
            ['project_index' => 8, 'name' => 'Low', 'color' => '#10B981'],
            // Project 9: Performance Optimization (completed)
            // Project 10: Payment Gateway
            ['project_index' => 10, 'name' => 'Stripe', 'color' => '#6366F1'],
            ['project_index' => 10, 'name' => 'PayPal', 'color' => '#0070BA'],
            ['project_index' => 10, 'name' => 'Security', 'color' => '#EF4444'],
            // Project 11: Inventory Management
            ['project_index' => 11, 'name' => 'Stock', 'color' => '#10B981'],
            ['project_index' => 11, 'name' => 'Orders', 'color' => '#3B82F6'],
            // Project 12: CRM Implementation
            ['project_index' => 12, 'name' => 'Sales', 'color' => '#EC4899'],
            ['project_index' => 12, 'name' => 'Marketing', 'color' => '#F59E0B'],
            ['project_index' => 12, 'name' => 'Support', 'color' => '#8B5CF6'],
            // Project 13: Social Media (completed)
            // Project 14: SEO
            ['project_index' => 14, 'name' => 'Technical', 'color' => '#6366F1'],
            ['project_index' => 14, 'name' => 'Content', 'color' => '#10B981'],
            // Project 15: Mobile Push Notifications
            ['project_index' => 15, 'name' => 'iOS', 'color' => '#3B82F6'],
            ['project_index' => 15, 'name' => 'Android', 'color' => '#10B981'],
            // Project 16: User Feedback
            ['project_index' => 16, 'name' => 'Bug Report', 'color' => '#EF4444'],
            ['project_index' => 16, 'name' => 'Feature Request', 'color' => '#10B981'],
            // Project 17: Data Warehouse
            ['project_index' => 17, 'name' => 'ETL', 'color' => '#6366F1'],
            ['project_index' => 17, 'name' => 'Database', 'color' => '#10B981'],
            // Project 18: Multi-Tenant
            ['project_index' => 18, 'name' => 'Architecture', 'color' => '#8B5CF6'],
            ['project_index' => 18, 'name' => 'Testing', 'color' => '#EC4899'],
            // Project 19: Accessibility
            ['project_index' => 19, 'name' => 'WCAG', 'color' => '#6366F1'],
            ['project_index' => 19, 'name' => 'Audit', 'color' => '#F59E0B'],
            // Project 20: Internationalization
            ['project_index' => 20, 'name' => 'i18n', 'color' => '#3B82F6'],
            ['project_index' => 20, 'name' => 'Translation', 'color' => '#10B981'],
        ];

        $labels = [];
        foreach ($labelsData as $labelData) {
            $project = $projects[$labelData['project_index']];
            $label = Label::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $labelData['name'],
                ],
                ['color' => $labelData['color']]
            );
            $labels[] = $label;
        }

        $this->command->info("✅ Created " . count($labels) . " labels.");
        return $labels;
    }

    private function seedTasks(array $users, array $projects, array $labels): void {
        $tasksData = $this->getTasksData();

        foreach ($tasksData as $projectIndex => $projectTasks) {
            $project = $projects[$projectIndex];

            foreach ($projectTasks as $taskData) {
                $task = Task::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'title' => $taskData['title'],
                    ],
                    [
                        'created_by' => $users[$taskData['created_by_key']]->id,
                        'description' => $taskData['description'],
                        'status' => $taskData['status'],
                        'priority' => $taskData['priority'],
                        'due_date' => $taskData['due_date'],
                        'sort_order' => $taskData['sort_order'],
                    ]
                );

                // Add assignees
                if (!empty($taskData['assignee_keys'])) {
                    $assigneeIds = array_map(
                        fn($key) => $users[$key]->id,
                        $taskData['assignee_keys']
                    );
                    $task->assignees()->syncWithoutDetaching($assigneeIds);
                }

                // Add labels
                if (!empty($taskData['label_indices'])) {
                    foreach ($taskData['label_indices'] as $labelIndex) {
                        if (isset($labels[$labelIndex])) {
                            $task->labels()->syncWithoutDetaching([$labels[$labelIndex]->id]);
                        }
                    }
                }

                // Add subtasks
                if (!empty($taskData['subtasks'])) {
                    foreach ($taskData['subtasks'] as $subtaskData) {
                        Subtask::updateOrCreate(
                            [
                                'task_id' => $task->id,
                                'title' => $subtaskData['title'],
                            ],
                            [
                                'is_done' => $subtaskData['is_done'],
                                'sort_order' => $subtaskData['sort_order'],
                            ]
                        );
                    }
                }
            }
        }

        // Update project progress based on task completion
        foreach ($projects as $project) {
            $totalTasks = $project->tasks()->count();
            $doneTasks = $project->tasks()->where('status', 'Done')->count();
            if ($totalTasks > 0) {
                $project->update(['progress' => round(($doneTasks / $totalTasks) * 100)]);
            }
        }

        $this->command->info("✅ Created tasks with assignees, labels, and subtasks.");
    }

    private function getTasksData(): array {
        return [
            // Website Redesign tasks (project index 0)
            0 => [
                [
                    'title' => 'Create wireframes for homepage',
                    'description' => 'Design low-fidelity wireframes for the new homepage layout including hero section, features, and testimonials.',
                    'created_by_key' => 'rupash',
                    'status' => 'Done',
                    'priority' => 'High',
                    'due_date' => now()->subDays(20),
                    'sort_order' => 1,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [3], // Design
                    'subtasks' => [
                        ['title' => 'Research competitor designs', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Sketch initial wireframes', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'Review with stakeholders', 'is_done' => true, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Implement responsive navigation',
                    'description' => 'Build a mobile-first responsive navigation with hamburger menu for smaller screens.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(5),
                    'sort_order' => 2,
                    'assignee_keys' => ['nishan', 'tanjim'],
                    'label_indices' => [2, 3], // Enhancement, Design
                    'subtasks' => [
                        ['title' => 'Desktop navigation', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Mobile hamburger menu', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Accessibility features', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'SEO optimization',
                    'description' => 'Optimize meta tags, headings, and content structure for search engines.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(15),
                    'sort_order' => 3,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [2], // Enhancement
                    'subtasks' => [
                        ['title' => 'Meta tags audit', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Update header hierarchy', 'is_done' => false, 'sort_order' => 2],
                    ],
                ],
                [
                    'title' => 'Performance audit',
                    'description' => 'Run Lighthouse audit and address performance issues.',
                    'created_by_key' => 'rupash',
                    'status' => 'Review',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(3),
                    'sort_order' => 4,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [2], // Enhancement
                    'subtasks' => [],
                ],
                [
                    'title' => 'Contact form integration',
                    'description' => 'Connect contact form to backend API with validation.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'High',
                    'due_date' => now()->addDays(10),
                    'sort_order' => 5,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [1], // Feature
                    'subtasks' => [
                        ['title' => 'Frontend form validation', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Backend endpoint', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Email notification', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Fix mobile menu animation',
                    'description' => 'The mobile menu animation stutters on iOS Safari. Investigate and fix.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(2),
                    'sort_order' => 6,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [0, 3], // Bug, Design
                    'subtasks' => [],
                ],
            ],
            // Mobile App tasks (project index 1)
            1 => [
                [
                    'title' => 'Setup React Native project',
                    'description' => 'Initialize React Native project with TypeScript and configure linting.',
                    'created_by_key' => 'rupash',
                    'status' => 'Done',
                    'priority' => 'Urgent',
                    'due_date' => now()->subDays(5),
                    'sort_order' => 1,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [5, 6], // iOS, Android
                    'subtasks' => [
                        ['title' => 'Initialize project', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Configure TypeScript', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'Setup ESLint/Prettier', 'is_done' => true, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Design authentication screens',
                    'description' => 'Create mockups for login, signup, and password reset screens.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(7),
                    'sort_order' => 2,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [8], // UI/UX
                    'subtasks' => [
                        ['title' => 'Login screen', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Signup screen', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'Password reset', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Implement OAuth authentication',
                    'description' => 'Integrate Google and Apple sign-in for mobile authentication.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Urgent',
                    'due_date' => now()->addDays(14),
                    'sort_order' => 3,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [7, 9], // Backend, Performance
                    'subtasks' => [
                        ['title' => 'Google Sign-In', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Apple Sign-In', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Token management', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Push notifications setup',
                    'description' => 'Configure Firebase Cloud Messaging for push notifications.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(21),
                    'sort_order' => 4,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [5, 6], // iOS, Android
                    'subtasks' => [
                        ['title' => 'iOS push certificates', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Android FCM setup', 'is_done' => false, 'sort_order' => 2],
                    ],
                ],
                [
                    'title' => 'Offline data sync',
                    'description' => 'Implement local storage and sync mechanism for offline support.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'High',
                    'due_date' => now()->addDays(30),
                    'sort_order' => 5,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [7, 9], // Backend, Performance
                    'subtasks' => [],
                ],
            ],
            // API Documentation tasks (project index 2)
            2 => [
                [
                    'title' => 'Document authentication endpoints',
                    'description' => 'Write comprehensive documentation for login, register, and password reset endpoints.',
                    'created_by_key' => 'rupash',
                    'status' => 'Done',
                    'priority' => 'High',
                    'due_date' => now()->subDays(5),
                    'sort_order' => 1,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [10], // Authentication
                    'subtasks' => [
                        ['title' => 'POST /api/login', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'POST /api/register', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'POST /api/password/reset', 'is_done' => true, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Document project endpoints',
                    'description' => 'Document all project CRUD operations with request/response examples.',
                    'created_by_key' => 'rupash',
                    'status' => 'Done',
                    'priority' => 'High',
                    'due_date' => now()->subDays(2),
                    'sort_order' => 2,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [11], // Endpoints
                    'subtasks' => [
                        ['title' => 'GET /api/projects', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'POST /api/projects', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'PUT /api/projects/{id}', 'is_done' => true, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Document task endpoints',
                    'description' => 'Document task CRUD and assignment endpoints.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(3),
                    'sort_order' => 3,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [11], // Endpoints
                    'subtasks' => [
                        ['title' => 'Task CRUD operations', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Task assignment', 'is_done' => false, 'sort_order' => 2],
                    ],
                ],
                [
                    'title' => 'Error codes documentation',
                    'description' => 'Compile all error codes and create troubleshooting guide.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(10),
                    'sort_order' => 4,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [12, 13], // Examples, Errors
                    'subtasks' => [
                        ['title' => 'HTTP status codes', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Validation errors', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Common issues', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Interactive API playground',
                    'description' => 'Integrate Swagger UI for interactive API testing.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Low',
                    'due_date' => now()->addDays(20),
                    'sort_order' => 5,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [12], // Examples
                    'subtasks' => [],
                ],
            ],
            // Legacy Migration tasks (project index 3)
            3 => [
                [
                    'title' => 'Audit existing Perl codebase',
                    'description' => 'Complete code audit to identify dependencies, bottlenecks, and refactoring priorities.',
                    'created_by_key' => 'rupash',
                    'status' => 'Done',
                    'priority' => 'High',
                    'due_date' => now()->subDays(30),
                    'sort_order' => 1,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [14], // Backend
                    'subtasks' => [
                        ['title' => 'Map dependencies', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Identify security issues', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'Document business logic', 'is_done' => true, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Design microservices architecture',
                    'description' => 'Create architecture diagrams and service decomposition plan.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'Urgent',
                    'due_date' => now()->addDays(15),
                    'sort_order' => 2,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [14, 17], // Backend, Deployment
                    'subtasks' => [
                        ['title' => 'Service boundary definitions', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'API gateway design', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Database per service', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Database schema migration',
                    'description' => 'Migrate MySQL database to PostgreSQL with proper indexing.',
                    'created_by_key' => 'rupash',
                    'status' => 'Review',
                    'priority' => 'High',
                    'due_date' => now()->addDays(60),
                    'sort_order' => 3,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [15], // Database
                    'subtasks' => [
                        ['title' => 'Schema mapping', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Data migration script', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Index optimization', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Authentication service rewrite',
                    'description' => 'Rewrite authentication module using JWT with OAuth2 support.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'High',
                    'due_date' => now()->addDays(90),
                    'sort_order' => 4,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [14, 16], // Backend, Testing
                    'subtasks' => [],
                ],
                [
                    'title' => 'Integration test suite',
                    'description' => 'Build comprehensive integration test suite for migrated services.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(120),
                    'sort_order' => 5,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [16], // Testing
                    'subtasks' => [
                        ['title' => 'Unit tests', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'Integration tests', 'is_done' => false, 'sort_order' => 2],
                        ['title' => 'Performance benchmarks', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
            ],
            // Project 4: E-Commerce Platform
            4 => [
                [
                    'title' => 'Setup payment gateway integration',
                    'description' => 'Integrate Stripe and PayPal payment processors.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Urgent',
                    'due_date' => now()->addDays(30),
                    'sort_order' => 1,
                    'assignee_keys' => ['debos', 'tanjim'],
                    'label_indices' => [18, 20], // Payment, Backend
                    'subtasks' => [
                        ['title' => 'Stripe setup', 'is_done' => false, 'sort_order' => 1],
                        ['title' => 'PayPal integration', 'is_done' => false, 'sort_order' => 2],
                    ],
                ],
                [
                    'title' => 'Product catalog implementation',
                    'description' => 'Build product listing, search, and filter functionality.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(45),
                    'sort_order' => 2,
                    'assignee_keys' => ['nishan', 'tanjim'],
                    'label_indices' => [19], // Frontend
                    'subtasks' => [],
                ],
            ],
            // Project 5: Customer Portal
            5 => [
                [
                    'title' => 'Design portal wireframes',
                    'description' => 'Create wireframes for customer dashboard and account pages.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(15),
                    'sort_order' => 1,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [21], // UI
                    'subtasks' => [
                        ['title' => 'Dashboard wireframes', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Account settings wireframes', 'is_done' => false, 'sort_order' => 2],
                    ],
                ],
                [
                    'title' => 'Build customer API endpoints',
                    'description' => 'Create REST API endpoints for customer data management.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'High',
                    'due_date' => now()->addDays(35),
                    'sort_order' => 2,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [22], // API
                    'subtasks' => [],
                ],
            ],
            // Project 6: Analytics Dashboard
            6 => [
                [
                    'title' => 'Implement chart components',
                    'description' => 'Build interactive chart components using Chart.js.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(20),
                    'sort_order' => 1,
                    'assignee_keys' => ['nishan'],
                    'label_indices' => [24], // Charts
                    'subtasks' => [
                        ['title' => 'Bar charts', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'Line charts', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'Pie charts', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Setup data pipeline',
                    'description' => 'Build ETL pipeline for processing analytics data.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(40),
                    'sort_order' => 2,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [25, 26], // Data, Backend
                    'subtasks' => [],
                ],
            ],
            // Project 8: Security Audit
            8 => [
                [
                    'title' => 'Penetration testing',
                    'description' => 'Conduct comprehensive penetration testing.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'Urgent',
                    'due_date' => now()->addDays(15),
                    'sort_order' => 1,
                    'assignee_keys' => ['tanjim', 'debos'],
                    'label_indices' => [27], // Critical
                    'subtasks' => [
                        ['title' => 'Network scanning', 'is_done' => true, 'sort_order' => 1],
                        ['title' => 'SQL injection testing', 'is_done' => true, 'sort_order' => 2],
                        ['title' => 'XSS testing', 'is_done' => false, 'sort_order' => 3],
                    ],
                ],
                [
                    'title' => 'Fix critical vulnerabilities',
                    'description' => 'Remediate critical security vulnerabilities found during audit.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Urgent',
                    'due_date' => now()->addDays(25),
                    'sort_order' => 2,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [27], // Critical
                    'subtasks' => [],
                ],
            ],
            // Project 10: Payment Gateway
            10 => [
                [
                    'title' => 'Stripe webhook handling',
                    'description' => 'Implement Stripe webhook event handling.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(20),
                    'sort_order' => 1,
                    'assignee_keys' => ['debos'],
                    'label_indices' => [30], // Stripe
                    'subtasks' => [],
                ],
                [
                    'title' => 'Payment security compliance',
                    'description' => 'Ensure PCI DSS compliance for payment processing.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'High',
                    'due_date' => now()->addDays(30),
                    'sort_order' => 2,
                    'assignee_keys' => ['tanjim', 'debos'],
                    'label_indices' => [32], // Security
                    'subtasks' => [],
                ],
            ],
            // Project 12: CRM Implementation
            12 => [
                [
                    'title' => 'Lead management module',
                    'description' => 'Build lead capture and tracking functionality.',
                    'created_by_key' => 'rupash',
                    'status' => 'In Progress',
                    'priority' => 'High',
                    'due_date' => now()->addDays(25),
                    'sort_order' => 1,
                    'assignee_keys' => ['debos', 'nishan'],
                    'label_indices' => [35], // Sales
                    'subtasks' => [],
                ],
                [
                    'title' => 'Email campaign integration',
                    'description' => 'Integrate email marketing tools with CRM.',
                    'created_by_key' => 'rupash',
                    'status' => 'Todo',
                    'priority' => 'Medium',
                    'due_date' => now()->addDays(40),
                    'sort_order' => 2,
                    'assignee_keys' => ['tanjim'],
                    'label_indices' => [36], // Marketing
                    'subtasks' => [],
                ],
            ],
        ];
    }
}
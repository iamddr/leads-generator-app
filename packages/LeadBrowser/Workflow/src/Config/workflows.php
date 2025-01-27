<?php

return [
    'trigger_entities' => [

        'search' => [
            'name'   => 'Search',
            'class'  => 'LeadBrowser\Workflow\Helpers\Entity\Search',
            'events' => [
                [
                    'event' => 'search.create.after',
                    'name'  => 'Created',  
                ], [
                    'event' => 'search.update.after',
                    'name'  => 'Updated',  
                ], [
                    'event' => 'search.delete.before',
                    'name'  => 'Deleted',  
                ],
            ]
        ],


        'leads' => [
            'name'   => 'Leads',
            'class'  => 'LeadBrowser\Workflow\Helpers\Entity\Lead',
            'events' => [
                [
                    'event' => 'lead.create.after',
                    'name'  => 'Created',  
                ], [
                    'event' => 'lead.update.after',
                    'name'  => 'Updated',  
                ], [
                    'event' => 'lead.delete.before',
                    'name'  => 'Deleted',  
                ],
            ]
        ],

        'activities' => [
            'name'   => 'Activities',
            'class'  => 'LeadBrowser\Workflow\Helpers\Entity\Activity',
            'events' => [
                [
                    'event' => 'activity.create.after',
                    'name'  => 'Created',  
                ], [
                    'event' => 'activity.update.after',
                    'name'  => 'Updated',  
                ], [
                    'event' => 'activity.delete.before',
                    'name'  => 'Deleted',  
                ],
            ]
        ],
        
        'employees' => [
            'name'   => 'Employees',
            'class'  => 'LeadBrowser\Workflow\Helpers\Entity\Employee',
            'events' => [
                [
                    'event' => 'employee.create.after',
                    'name'  => 'Created',  
                ], [
                    'event' => 'employee.update.after',
                    'name'  => 'Updated',  
                ], [
                    'event' => 'employee.delete.before',
                    'name'  => 'Deleted',  
                ],
            ]
        ],

        'quotes' => [
            'name'   => 'Quotes',
            'class'  => 'LeadBrowser\Workflow\Helpers\Entity\Quote',
            'events' => [
                [
                    'event' => 'quote.create.after',
                    'name'  => 'Created',  
                ], [
                    'event' => 'quote.update.after',
                    'name'  => 'Updated',  
                ], [
                    'event' => 'quote.delete.before',
                    'name'  => 'Deleted',  
                ],
            ]
        ]
    ]
];
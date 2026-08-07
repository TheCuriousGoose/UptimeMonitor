<?php

return [
    'title' => 'Content',
    'heading' => 'Content',
    'subtitle' => 'Documentation, blog posts and changelog entries.',
    'types' => [
        'doc' => 'Documentation',
        'post' => 'Blog post',
        'changelog' => 'Changelog',
    ],
    'types_plural' => [
        'doc' => 'Documentation',
        'post' => 'Blog',
        'changelog' => 'Changelog',
    ],
    'status' => [
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'draft' => 'Draft',
    ],
    'empty' => [
        'title' => 'Nothing here yet',
        'description' => 'Publish your first entry and it will appear on the public site.',
    ],
    'table' => [
        'empty' => 'No entries found.',
        'columns' => [
            'title' => 'Title',
            'type' => 'Type',
            'status' => 'Status',
            'updated' => 'Updated',
            'actions' => 'Actions',
        ],
        'filters' => [
            'search' => ['placeholder' => 'Search entries...'],
            'type' => ['label' => 'Type', 'all' => 'All types'],
        ],
    ],
    'form' => [
        'create' => 'New entry',
        'edit' => 'Edit entry',
        'submit' => 'Save entry',
        'type' => ['title' => 'Type'],
        'title' => ['title' => 'Title', 'placeholder' => 'Getting started'],
        'slug' => [
            'title' => 'Slug',
            'placeholder' => 'getting-started',
            'description' => 'Leave blank to generate one from the title.',
        ],
        'excerpt' => [
            'title' => 'Excerpt',
            'placeholder' => 'A one-line summary shown in listings.',
        ],
        'body' => [
            'title' => 'Body',
            'placeholder' => '# Heading\n\nWrite in markdown.',
            'description' => 'Markdown. Raw HTML is stripped when rendered.',
        ],
        'version' => ['title' => 'Version', 'placeholder' => 'v1.2.0'],
        'category' => ['title' => 'Category', 'placeholder' => 'Getting started'],
        'sort_order' => ['title' => 'Sort order'],
        'published_at' => [
            'title' => 'Publish date',
            'description' => 'Leave blank to keep it a draft. A future date schedules it.',
        ],
    ],
    'actions' => [
        'edit' => 'Edit',
        'view' => 'View public page',
        'delete' => 'Delete',
        'confirm_delete' => 'Delete ":name"? It will disappear from the public site.',
    ],
    'messages' => [
        'created' => 'Entry created',
        'updated' => 'Entry updated',
        'deleted' => 'Entry deleted',
    ],
    'public' => [
        'docs' => [
            'title' => 'Documentation',
            'subtitle' => 'Everything you need to run Vigil Watch.',
            'empty' => 'No documentation has been published yet.',
        ],
        'blog' => [
            'title' => 'Blog',
            'subtitle' => 'Product news and engineering notes.',
            'empty' => 'No posts have been published yet.',
            'read' => 'Read post',
        ],
        'changelog' => [
            'title' => 'Changelog',
            'subtitle' => 'What shipped, and when.',
            'empty' => 'No releases have been published yet.',
        ],
        'back' => 'Back',
        'uncategorised' => 'General',
    ],
];

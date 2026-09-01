<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum EvidenceType: string
{
    use HasLabels;

    case GithubRepository = 'github-repository';
    case GitlabRepository = 'gitlab-repository';
    case BitbucketRepository = 'bitbucket-repository';
    case PullRequest = 'pull-request';
    case PersonalWebsite = 'personal-website';
    case PortfolioWebsite = 'portfolio-website';
    case BlogPost = 'blog-post';
    case TechnicalArticle = 'technical-article';
    case Documentation = 'documentation';
    case Package = 'package';
    case OpenSourceProject = 'open-source-project';
    case ConferenceTalk = 'conference-talk';
    case Video = 'technical-video';
    case CaseStudy = 'case-study';
    case ProductDemo = 'product-demo';
    case Presentation = 'technical-presentation';
    case ArchitectureDocument = 'architecture-document';
    case Project = 'project';

    public const LABELS = [
        'github-repository' => 'GitHub Repository',
        'gitlab-repository' => 'GitLab Repository',
        'bitbucket-repository' => 'Bitbucket Repository',
        'pull-request' => 'Pull Request',
        'personal-website' => 'Personal Website',
        'portfolio-website' => 'Portfolio Website',
        'blog-post' => 'Blog Post',
        'technical-article' => 'Technical Article',
        'documentation' => 'Documentation',
        'package' => 'Package',
        'open-source-project' => 'Open Source Project',
        'conference-talk' => 'Conference Talk',
        'technical-video' => 'Technical Video',
        'case-study' => 'Case Study',
        'product-demo' => 'Product Demo',
        'technical-presentation' => 'Technical Presentation',
        'architecture-document' => 'Architecture Document',
        'project' => 'Project',
    ];
}

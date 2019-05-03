<?php

namespace App\RepoCrawler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use App\Service\ProjectMetricsProvider;
use App\Entity\GitSourceRepo;
use App\Entity\SourceRepo;
use App\Repository\CommitRepository;
use App\Repository\ContributorRepository;
use App\Util\MarkupToHtmlConverter;
use App\Util\ProcessCreator;
use App\Service\GitHub\GitHubApiService;
use Psr\Log\LoggerInterface;

/**
 * Repository crawler factory: get an appropriate repository crawler instance
 */
class RepoCrawlerFactory
{
    /**
     * @var MarkupToHtmlConverter
     */
    private $markupConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ProcessCreator
     */
    private $processCreator;

    /**
     * @var GitHubApiService
     */
    private $ghApi;

    private $projectMetricsProvider;

    /**
     * @var CommitRepository
     */
    private $commitRepository;

    /**
     * @var ContributorRepository
     */
    private $contributorRepository;

    /**
     * Constructor: create a new instance
     *
     * @param MarkupToHtmlConverter  $markupConverter
     * @param LoggerInterface        $logger
     * @param CommitRepository       $commitRepository
     * @param ContributorRepository  $contributorRepository
     * @param ObjectManager          $manager
     * @param ProcessCreator         $processCreator
     * @param GitHubApiService       $ghApi
     * @param ProjectMetricsProvider $projectMetricsProvider
     */
    public function __construct(
        MarkupToHtmlConverter $markupConverter,
        LoggerInterface $logger,
        CommitRepository $commitRepository,
        ContributorRepository $contributorRepository,
        ObjectManager $manager,
        ProcessCreator $processCreator,
        GitHubApiService $ghApi,
        ProjectMetricsProvider $projectMetricsProvider
    ) {
        $this->markupConverter = $markupConverter;
        $this->logger = $logger;
        $this->commitRepository = $commitRepository;
        $this->manager = $manager;
        $this->processCreator = $processCreator;
        $this->ghApi = $ghApi;
        $this->projectMetricsProvider = $projectMetricsProvider;
        $this->contributorRepository = $contributorRepository;
    }

    /**
     * Get a RepoCrawler subclass for the source repository
     *
     * @param SourceRepo $repo
     *
     * @return RepoCrawler
     *
     * @throws InvalidArgumentException if the source repository type is not
     *                                   supported by an available crawler
     */
    public function getCrawlerForSourceRepo(SourceRepo $repo): RepoCrawler
    {
        // XXX: Investigate a better method for IoC in this situation
        if ($repo instanceof GitSourceRepo) {
            if (GithubRepoCrawler::isGithubRepoUrl($repo->getUrl())) {
                return new GithubRepoCrawler(
                    $repo,
                    $this->markupConverter,
                    $this->processCreator,
                    $this->commitRepository,
                    $this->contributorRepository,
                    $this->manager,
                    $this->logger,
                    $this->ghApi,
                    $this->projectMetricsProvider
                );
            }

            return new GitRepoCrawler(
                $repo,
                $this->markupConverter,
                $this->processCreator,
                $this->commitRepository,
                $this->contributorRepository,
                $this->manager,
                $this->logger,
                $this->projectMetricsProvider
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                "No crawler for source repository of type %s found.",
                get_class($repo)
            )
        );
    }
}
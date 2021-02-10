<?php


namespace App\Command;


use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePullRequestStatusCommand extends Command
{
    protected static $defaultName = 'deployment:status';

    private Client $client;
    private string $githubOwner;
    private string $githubRepository;

    public function __construct(Client $client, string $githubOwner, string $githubRepository, string $name = null)
    {
        parent::__construct($name);
        $this->client = $client;
        $this->githubOwner = $githubOwner;
        $this->githubRepository = $githubRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Greet someone')
            ->addArgument(
                'sha1',
                InputArgument::REQUIRED,
                'Which SHA1 to set the status ?'
            )
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The URL to put on the detail inside commit status'
            )
            ->addArgument(
                'status',
                InputArgument::REQUIRED,
                'Status of the job'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sha1   = $input->getArgument('sha1');
        $url    = $input->getArgument('url');
        $status = $input->getArgument('status');

        switch ($status) {
            case 'success':
                $comment = "The pull request has been deployed";
                break;
            case 'error':
            case 'failure':
                $comment = "The pull request cannot be deployed";
                break;
            default:
                $comment = "The pull request is trying to be deployed";
        }

        $params = [
            'state'       => $status,
            'target_url'  => ($status == 'success') ? $url : null,
            'description' => $comment,
            'context'     => 'tower/pr-builder',
        ];

        try {
            $response = $this->client->api('repos')->statuses()->create($this->githubOwner, $this->githubRepository, $sha1, $params);
            $output->writeln($response['id']);

            return 0;
        } catch (\Exception $e) {
            $this->getApplication()->renderThrowable($e, $output);

            return 1;
        }
    }
}

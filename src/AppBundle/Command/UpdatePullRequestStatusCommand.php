<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github\Client;

class UpdatePullRequestStatusCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deployment:status')
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sha1   = $input->getArgument('sha1');
        $url    = $input->getArgument('url');
        $status = $input->getArgument('status');

        $container = $this->getContainer();

        $user       = $container->getParameter('github_user');
        $password   = $container->getParameter('github_password');
        $owner      = $container->getParameter('github_owner');
        $repository = $container->getParameter('github_repository');

        $client = new Client();
        $client->authenticate($user, $password, Client::AUTH_HTTP_PASSWORD);

        switch ($status) {
            case 'success':
                $comment = "The pull request has been deployed";
                break;
            case 'failed':
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

        $response = $client->api('repos')->statuses()->create($owner, $repository, $sha1, $params);
        $output->writeln($response['id']);
    }
}


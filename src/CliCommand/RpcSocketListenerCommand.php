<?php

namespace Ufo\RpcMercure\CliCommand;

use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Ufo\RpcMercure\DTO\MercureConfig;
use Ufo\RpcMercure\Services\RpcSocketTransport;

use function str_repeat;

#[AsCommand(
    name: RpcSocketListenerCommand::COMMAND_NAME,
    description: 'Handle async rpc request on socket',
)]
class RpcSocketListenerCommand extends Command
{
    const string COMMAND_NAME = 'ufo:rpc:socket:consume';

    protected SymfonyStyle $io;

    public function __construct(
        protected RpcSocketTransport $rpcClient,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'topic',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Topic to listen. Default: ' . MercureConfig::REQUEST,
                default: MercureConfig::REQUEST,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = $io = new SymfonyStyle($input, $output);
        $topic = $this->rpcClient->mercureConfig->getTopic($input->getOption('topic'), false);
        $io->block('Connect to socket: ' . $topic, style: 'fg=green');
        try {
            $detail = $output->isVerbose();
            $this->rpcClient->fetch(
                $input->getOption('topic'),
                function (string $data, bool $isError = false, bool $isWarning = false) use ($io, $detail): void
                {
                    $color = $isError ? 'red' : 'cyan';
                    if ($isWarning) {
                        $color = 'yellow';
                        if (!$detail) return;
                    }
                    $this->printBlock($data, $color, date: true, end: true);
                }
            );
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function printBlock(string $msg, string $color, bool $date = false, bool $end = false): void
    {
        if ($date) $this->io->writeln(new DateTimeImmutable()->format('Y-m-d H:i:s'));
        $this->io->writeln("<fg={$color}>{$msg}</>");
        if ($end) $this->io->writeln(str_repeat('=', 100));
    }
}

<?php

namespace App\Command;

use App\Message\UpdatePosRatiosMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * POS oranlarının asenkron olarak güncellenmesi için mesajı kuyruğa gönderir.
 */
class RefreshPosRatiosCommand extends Command
{
    protected static $defaultName = 'app:refresh-pos-ratios';
    protected static $defaultDescription = 'Dispatches a message to refresh POS ratios.';

    private MessageBusInterface $bus;

    /**
     * @param MessageBusInterface $bus
     */
    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Dispatching a message to update POS ratios...');

        // Mesajı kuyruk sistemine gönder
        $this->bus->dispatch(new UpdatePosRatiosMessage());

        $output->writeln('Message dispatched successfully! The worker will handle the update process asynchronously.');

        return Command::SUCCESS;
    }
}
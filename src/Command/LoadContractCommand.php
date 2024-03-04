<?php

namespace App\Command;

use App\Entity\Contract;
use App\Factory\ContractFactory;
use App\Factory\ReceiptFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load:contract',
    description: 'Load lot of contract with random values',
)]
class LoadContractCommand extends Command
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private ContractFactory $contractFactory,
        private ReceiptFactory $receiptFactory,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
<<<EOS
Will load a lot of contract (10 by default) with random values
<info>symfony console app:load:contract</info>

Load specific number of contract (e.g. 50)
<info>symfony console app:load:contract 50</info>

Will display the contract to be created without persisting them
<info>symfony console app:load:contract 50 --dry-run</info>
EOS

            )
            ->addArgument('nb', InputArgument::OPTIONAL, 'Number of contract to load', 10)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode (no persist)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        gc_disable();

        $io = new SymfonyStyle($input, $output);
        $nbContract = (int)$input->getArgument('nb');
        $dryRunMode = $input->getOption('dry-run');

        $io->title(sprintf('Load %s contract', $nbContract));
        $io->progressStart($input->getArgument('nb'));

        try {
            for ($i = 0; $i < $nbContract; $i++) {
                $debitMode = $this->getRandomDebitMode();

                $effectiveDate = $this->getRandomDate();
                $endEffectiveDate = clone $effectiveDate;
                $endEffectiveDate
                    ->modify('+1 year')
                    ->modify('-1 day');

                $status = Contract::STATUS_IN_PROGRESS;
                if ($endEffectiveDate < new DateTime()) {
                    $status = Contract::STATUS_TERMINATED;
                }

                // random float between 15 and 5000
                $annualPrimeTtc = random_int(1500, 500000) / 100;

                $recurrence = $this->getRandomRecurrence();

                $contract = $this->contractFactory->create(
                    status: $status,
                    debitDay: random_int(1, 31),
                    debitMode: $debitMode,
                    effectiveDate: $effectiveDate,
                    endEffectiveDate: $endEffectiveDate,
                    annualPrimeTtc: $annualPrimeTtc,
                    recurrence: $recurrence,
                );

                $receipt = $this->receiptFactory->createFirst(
                    contract: $contract,
                );

                // wait a bit to have different created_at and external_id
                usleep(10000);

                if (!$dryRunMode) {
                    $this->em->persist($contract);
                    $this->em->persist($receipt);

                    if ($i % self::BATCH_SIZE === 0) {
                        $this->em->flush();
                        $this->em->clear();
                        gc_collect_cycles();
                    }
                }
                $io->progressAdvance();
            }

            if (!$dryRunMode) {
                $this->em->flush();
                $this->em->clear();
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        $io->progressFinish();
        $io->success('Contracts loaded with its receipts');
        if ($dryRunMode) {
            $io->warning('Dry run mode, no contract persisted');
        }

        return Command::SUCCESS;
    }

    private function getRandomDebitMode(): string
    {
        return Contract::ALL_DEBIT_MODE[array_rand(Contract::ALL_DEBIT_MODE)];
    }

    private function getRandomStatus(string $debitMode): string
    {
        $status = Contract::STATUS_IN_PROGRESS;

        if ($debitMode !== Contract::DEBIT_MODE_NOTHING) {
            $status = Contract::ALL_STATUS[array_rand(Contract::ALL_STATUS)];
        }

        return $status;
    }

    private function getFirstRandomContractStatus(): string
    {
        // 9 chance to have in progress and 1 chance to have terminated
        $statusArray = array_fill(0, 9, Contract::STATUS_IN_PROGRESS);
        $statusArray[] = Contract::STATUS_TERMINATED;

        return $statusArray[array_rand($statusArray)];
    }

    private function getRandomRecurrence(): string
    {
        return Contract::ALL_RECURRENCE[array_rand(Contract::ALL_RECURRENCE)];
    }

    private function getRandomDate(): DateTime
    {
        $now = new DateTime();
        $pastYear = (clone $now)->modify('-18 months');

        $randomTimestamp = random_int($pastYear->getTimestamp(), $now->getTimestamp());
        return (new DateTime())->setTimestamp($randomTimestamp);
    }
}

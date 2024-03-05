<?php

namespace App\Command;

use App\Entity\Contract;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\Factory\TransactionFactory;
use App\Service\ContractService;
use App\Service\MockDebitService;
use App\Service\ReceiptService;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:launch-debit',
    description: 'Launch debit plan with more options',
)]
class LaunchDebitCommand extends Command
{
    private SymfonyStyle $io;
    private const BATCH_SIZE = 50;

    /**
     * @param SymfonyStyle $io
     */
    public function __construct(
        private ContractService $contractService,
        private ReceiptService $receiptService,
        private MockDebitService $mockDebitService,
        private TransactionFactory $transactionFactory,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
<<<EOS
Will launch debit on all contract en rapport avec la date d'aujourd'hui
<info>symfony console app:launch-debit</info>

Will launch debit on all contract en rapport on a specific date (with yyyy-mm-dd format)
<info>symfony console app:launch-debit 2024-03-05</info>

Will launch debit on specific contract
<info>symfony console app:launch-debit --ref='1709101437617' --ref='1709101437151'</info>

Will launch debit on specific date with specific contract and without persisting them
<info>symfony console app:launch-debit 2024-03-08 --ref='1709101437617' --ref='1709101437151' --dry-run</info>
EOS

            )
            ->addArgument(
                'debit-date',
                InputArgument::OPTIONAL,
                'Date of the debit',
                (new DateTime())->format('Y-m-d')
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode (no persist)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Launch debit command');

        $inputDebitDate = $input->getArgument('debit-date');
        $dryRunMode = $input->getOption('dry-run');
        $debitDate = $this->isCorrectDate($inputDebitDate) ? new DateTimeImmutable($inputDebitDate) : null;

        if (null === $debitDate) {
            $this->io->error(sprintf("The debit date requested (%s) does not have a correct format.", $inputDebitDate));
            return Command::INVALID;
        }

        // TODO see to don't call the same contract twice
        $contracts = $this->contractService->findContractsToBilling($debitDate);
        $nbContract = count($contracts);

        if ($nbContract === 0) {
            $this->io->warning('No contract to collect');
            return Command::SUCCESS;
        }

        $this->io->info(sprintf(
            'Found %d contracts to collect',
            $nbContract
        ));

        $this->io->section(sprintf(
            'Contracts impacted (external_id with id) by the debit on %s',
            $debitDate->format('Y-m-d'))
        );
        foreach ($contracts as $contract) {
            $this->io->writeln(sprintf(' - %s (%s)', $contract->getExternalId(), $contract->getId()));
        }

        $confirmResult = $this->io->confirm('Do you want to continue ?', false);

        if (!$confirmResult) {
            $this->io->warning('Operation aborted');
            return Command::INVALID;
        }

        try {
            gc_disable();
            $i = 0;
            $tableHeader = ['Contract', 'Receipt', 'Transaction ID', 'Result'];
            $tableRow = [];
            $this->io->progressStart($nbContract);
            foreach ($contracts as $contract) {
                $i++;
                $receipt = $this->receiptService->getOrCreateReceipt($contract, $debitDate);

                $transaction = null;
                if (Contract::DEBIT_MODE_NOTHING !== $contract->getDebitMode()) {
                    $transaction = $this->transactionFactory->create($receipt);
                    if (!$dryRunMode) {
                        $this->em->persist($transaction);
                    }

                    $this->mockDebitService->debit($transaction);
                }

                $tableRow[] = $this->getTableRow($contract, $receipt, $transaction);

                if (!$dryRunMode) {
                    $this->em->persist($contract);
                    $this->em->persist($receipt);

                    if ($i % self::BATCH_SIZE === 0) {
                        $this->em->flush();
                        $this->em->clear();
                        gc_collect_cycles();
                    }
                }
                $this->io->progressAdvance();
            }

            if (!$dryRunMode) {
                $this->em->flush();
                $this->em->clear();
            }

            $this->io->writeln('');
            $this->io->table($tableHeader, $tableRow);


        } catch (Exception $e) {
            $this->io->error($contract->getId() . ' - ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            gc_enable();
        }

        $this->io->progressFinish();
        $this->io->success('Contracts loaded with its receipt and transaction');
        if ($dryRunMode) {
            $this->io->warning('Dry run mode, no contract persisted');
        }
        return Command::SUCCESS;
    }

    private function isCorrectDate(string $dateToCheck)
    {
        // check if it is a date
        try {
            $convertDate = new DateTime($dateToCheck);
        } catch (Exception $e) {
            return false;
        }

        // check if the convert date is the same that origin string date
        return $convertDate->format('Y-m-d') === $dateToCheck;
    }

    private function getTableRow(Contract $contract, Receipt $receipt, ?Transaction $transaction = null): array
    {
        $contractInfo = sprintf('%s (%s) - %s - %s :%s€',
            $contract->getId(),
            $contract->getRecurrence(),
            $contract->getDebitMode(),
            $contract->getStatus(),
            $contract->getAnnualPrimeTtc(),
        );
        $receiptInfo = sprintf('%s - %s to %s - %s - %s€',
            $receipt->getId(),
            $receipt->getStartApplyAt()?->format('Y-m-d'),
            $receipt->getEndApplyAt()?->format('Y-m-d'),
            $receipt->getStatus(),
            $receipt->getAmountTtc(),
        );

        $resultInfo = $transaction?->getStatus() ?? '';
        if ($transaction?->getFailureReason()) {
            $resultInfo = sprintf('%s - failureReason: %s',
                $transaction->getStatus(),
                $transaction->getFailureReason(),
            );
        }
        return [
            $contractInfo,
            $receiptInfo,
            $transaction?->getId() ?? '',
            $resultInfo,
        ];
    }
}

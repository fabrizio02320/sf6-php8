<?php

namespace App\Command;

use App\Entity\Contract;
use App\Service\ContractService;
use DateTime;
use DateTimeImmutable;
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
    public function __construct(private ContractService $contractService)
    {
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
            ->addOption(
                'ref',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'List of external ids'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode (no persist)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Launch debit command');

        $inputDebitDate = $input->getArgument('debit-date');
        $debitDate = $this->isCorrectDate($inputDebitDate) ? new DateTimeImmutable($inputDebitDate) : null;

        if (null === $debitDate) {
            $this->io->error(sprintf("The debit date requested (%s) does not have a correct format.", $inputDebitDate));
            return Command::INVALID;
        }

        $contracts = $this->contractService->findContractsToBilling($debitDate);
        $nbContract = count($contracts);
        // get le nombre de quittance qui sera générée
        // get le nombre de relance qui sera envoyée
        // recap nb de contract impacté ? (+ nb de quittance a créér + nb transaction a realiser)

        $this->io->info(sprintf(
            'Found %d contracts to collect',
            $nbContract
        ));
        $this->io->section(sprintf('Contracts impacted (external_id with id) by the debit on %s', $debitDate->format('Y-m-d')));
        foreach ($contracts as $contract) {
            $this->io->writeln(sprintf(' - %s (%s)', $contract->getExternalId(), $contract->getId()));
        }

        $confirmResult = $this->io->confirm('Do you want to continue ?', false);

        if (!$confirmResult) {
            $this->io->warning('Operation aborted');
            return Command::INVALID;
        }

        // boucle sur les contrat impacté
        foreach ($contracts as $contract) {
            $receiptDate = $debitDate;
            if (Contract::DEBIT_MODE_NOTHING === $contract->getDebitMode()) {
                $receiptDate = $receiptDate->modify('+30 days');
            }

            $receipt = $contract->getReceiptOnDate($receiptDate);
        }
            // create quittance si pas deja existante, sinon get it
            // create transaction avec statut create sur la quittance, si mode de paiement du contrat != aucun
            // result = mock du paiement avec réponse aléatoire
            // en fonction du result, maj contract + quittance + transaction

        // END BOUCLE

        // Display recapitulatif des resultats de prélèvement par contract

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
}

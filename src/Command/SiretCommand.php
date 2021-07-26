<?php

namespace App\Command;

use App\Entity\Society;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SiretCommand extends Command {

    protected static $defaultName = 'app:siret';

    private $manager;

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription("Trouver une société par son siret")
            ->addArgument('siret', InputArgument::REQUIRED, "Siret");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = "https://api.insee.fr/entreprises/sirene/V3/siret/".$input->getArgument("siret");
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer d3d23269-a13c-3bcb-8bca-74f27046031c",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($resp);
        $society = new Society();
        $society->setSiren($data->etablissement->siren);
        $society->setSiret($data->etablissement->siret);
        $society->setDateCreation(new \DateTime($data->etablissement->dateCreationEtablissement));
        $society->setName($data->etablissement->uniteLegale->denominationUniteLegale);
        $this->manager->persist($society);
        $this->manager->flush();
        $output->writeln([
            'ok'
        ]);
        return 0;
    }
}

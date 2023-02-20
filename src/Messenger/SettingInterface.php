<?php
namespace Coa\MessengerBundle\Messenger;


/**
 * interface representant de la base de données du middleware
 * les stock:
 * - id: l'identifiant unique du producer
 * - token: le secret servant chiffrer les communications
 * - producers: les autres producers d'evenements
 *
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
interface SettingInterface{
    /**
     * retourne d'identifiant unique du producer
     *
     * @return string
     */
    public function getId(): string;

    /**
     * retourne le token du producer servant a creer la signature des messages envoyés
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * retourne la liste des producers de messages enregistrées localement et certifiée
     *
     * @return array
     */
    public function getProducers(): array;

    /**
     * vérifie l'intégrité du fichier de la base de donnée locale
     *
     * @param string $db_file
     * @param string $key_file
     * @return mixed
     */
    public static function checkIntegrity(string $db_file, string $key_file);

    /**
     * creer le fichier de base de donnée si celui ci n'existe pas encore
     *
     * @param string $db_file
     * @param string $key_file
     * @return SettingInterface|null
     */
    public static function create(string $db_file, string $key_file): ?SettingInterface;

    /**
     * chargement les informations de la base de donnée et retourne les données
     *
     * @param string $db_file
     * @param string $key_file
     * @return SettingInterface|null
     */
    public static function loadData(string $db_file, string $key_file): ?SettingInterface;

    /**
     * sauvegarde les informations de la base de données
     *
     * @param string $db_file
     * @param string $key_file
     * @return $this
     */
    public function save(string $db_file, string $key_file) : self;

    /**
     * verifie la presence d'un producer dans la base de données
     *
     * @param Producer $target
     * @return bool
     */
    public function hasProducer(Producer $target): bool;

    /**
     * ajoute un producer dans la base de données
     *
     * @param Producer $item
     * @return $this
     */
    public function addProducer(Producer $item) : self;

    /**
     * supprime un producer de la base de données
     *
     * @param Producer $target
     * @param Producer|null $replaceWith
     * @return $this
     */
    public function removeProducer(Producer $target, ?Producer $replaceWith = null) : self;

    /**
     * @param WhoIsRequest $target
     * @return bool
     */
    public function hasWhoIsRequest(WhoIsRequest $target): bool;

    /**
     * @param WhoIsRequest $item
     * @return $this
     */
    public function addWhoIsRequest(WhoIsRequest $item): self;

    /**
     * @param WhoIsRequest $target
     * @return $this
     */
    public function removeWhoIsRequest(WhoIsRequest $target) : self;
}
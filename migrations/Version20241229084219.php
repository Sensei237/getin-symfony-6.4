<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241229084219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annee_academique (id INT AUTO_INCREMENT NOT NULL, denomination VARCHAR(20) NOT NULL, slug VARCHAR(100) NOT NULL, is_archived TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A6971EA815AEA10C (denomination), UNIQUE INDEX UNIQ_A6971EA8989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE anonymat (id INT AUTO_INCREMENT NOT NULL, contrat_id INT NOT NULL, examen_id INT NOT NULL, anonymat VARCHAR(255) NOT NULL, INDEX IDX_FC64F11823061F (contrat_id), INDEX IDX_FC64F15C8659A (examen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, specialite_id INT NOT NULL, formation_id INT DEFAULT NULL, nom VARCHAR(150) NOT NULL, code VARCHAR(20) NOT NULL, slug VARCHAR(150) NOT NULL, niveau INT NOT NULL, UNIQUE INDEX UNIQ_8F87BF966C6E55B5 (nom), UNIQUE INDEX UNIQ_8F87BF9677153098 (code), UNIQUE INDEX UNIQ_8F87BF96989D9B62 (slug), INDEX IDX_8F87BF962195E0F0 (specialite_id), INDEX IDX_8F87BF965200282E (formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE configuration (id INT AUTO_INCREMENT NOT NULL, annee_academique_id INT NOT NULL, nom_ecole VARCHAR(255) NOT NULL, initiale VARCHAR(255) NOT NULL, slogan VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) NOT NULL, note_pour_valider_une_matiere DOUBLE PRECISION DEFAULT NULL, is_validation_modulaire TINYINT(1) NOT NULL, note_pour_valider_un_module DOUBLE PRECISION DEFAULT NULL, note_eliminatoire DOUBLE PRECISION DEFAULT NULL, boite_postale VARCHAR(255) DEFAULT NULL, is_srecrase_sn TINYINT(1) NOT NULL, is_rattrapage_sur_toutes_les_matieres TINYINT(1) NOT NULL, nom_ecole_en VARCHAR(255) DEFAULT NULL, logo_university VARCHAR(255) DEFAULT NULL, pourcentage_ecfor_adc INT NOT NULL, UNIQUE INDEX UNIQ_A5E2A5D7B00F076 (annee_academique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contrat (id INT AUTO_INCREMENT NOT NULL, etudiant_inscris_id INT NOT NULL, ec_module_id INT NOT NULL, contrat_precedent_id INT DEFAULT NULL, note_cc DOUBLE PRECISION DEFAULT NULL, note_sn DOUBLE PRECISION DEFAULT NULL, note_sr DOUBLE PRECISION DEFAULT NULL, is_dette TINYINT(1) NOT NULL, is_validated TINYINT(1) NOT NULL, moy_avant_rattrapage DOUBLE PRECISION DEFAULT NULL, moy_apres_rattrapage DOUBLE PRECISION DEFAULT NULL, moy_apres_jury DOUBLE PRECISION DEFAULT NULL, moy_definitive DOUBLE PRECISION DEFAULT NULL, grade VARCHAR(10) DEFAULT NULL, decision VARCHAR(25) DEFAULT NULL, note DOUBLE PRECISION DEFAULT NULL, note_jury DOUBLE PRECISION DEFAULT NULL, INDEX IDX_603499933EB5182D (etudiant_inscris_id), INDEX IDX_603499933C651B4 (ec_module_id), INDEX IDX_603499934BB6D30 (contrat_precedent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, region_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, INDEX IDX_C1765B6398260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ec (id INT AUTO_INCREMENT NOT NULL, intitule VARCHAR(150) NOT NULL, code VARCHAR(20) NOT NULL, slug VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_8DE8BDFF989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ecmodule (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, ec_id INT NOT NULL, credit DOUBLE PRECISION NOT NULL, semestre INT NOT NULL, is_optionnal TINYINT(1) NOT NULL, INDEX IDX_FA91A045AFC2B591 (module_id), INDEX IDX_FA91A04527634BEF (ec_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employe (id INT AUTO_INCREMENT NOT NULL, service_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) DEFAULT NULL, date_de_naissance DATE NOT NULL, lieu_de_naissance VARCHAR(255) NOT NULL, sexe VARCHAR(20) NOT NULL, telephone VARCHAR(20) NOT NULL, photo VARCHAR(255) DEFAULT NULL, telephone2 VARCHAR(20) DEFAULT NULL, adresse_email VARCHAR(100) DEFAULT NULL, grade VARCHAR(100) DEFAULT NULL, situation_matrimoniale VARCHAR(100) NOT NULL, nombre_denfants INT NOT NULL, nom_conjoint VARCHAR(255) DEFAULT NULL, telephone_conjoint VARCHAR(20) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, is_visible TINYINT(1) NOT NULL, is_gone TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_F804D3B9450FF010 (telephone), UNIQUE INDEX UNIQ_F804D3B9727A199 (telephone2), UNIQUE INDEX UNIQ_F804D3B988D20D42 (adresse_email), UNIQUE INDEX UNIQ_F804D3B9AEA34913 (reference), INDEX IDX_F804D3B9ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE etudiant (id INT AUTO_INCREMENT NOT NULL, departement_id INT DEFAULT NULL, pays_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) DEFAULT NULL, date_de_naissance DATE NOT NULL, lieu_de_naissance VARCHAR(255) DEFAULT NULL, sexe VARCHAR(20) NOT NULL, nom_du_pere VARCHAR(255) DEFAULT NULL, numero_de_telephone_du_pere VARCHAR(255) DEFAULT NULL, nom_de_la_mere VARCHAR(255) DEFAULT NULL, numero_de_telephone_de_la_mere VARCHAR(255) DEFAULT NULL, adresse_des_parents VARCHAR(255) DEFAULT NULL, telephone1 VARCHAR(20) DEFAULT NULL, telephone2 VARCHAR(255) DEFAULT NULL, adresse_email VARCHAR(255) DEFAULT NULL, nombre_denfants INT NOT NULL, situation_matrimoniale VARCHAR(100) DEFAULT NULL, civilite VARCHAR(50) DEFAULT NULL, diplome_academique_max VARCHAR(255) DEFAULT NULL, annee_obtention_diplome_academique_max INT DEFAULT NULL, diplome_dentre VARCHAR(255) DEFAULT NULL, annee_obtention_diplome_entre INT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, profession_du_pere VARCHAR(255) DEFAULT NULL, profession_de_la_mere VARCHAR(255) DEFAULT NULL, personne_acontacter_en_cas_de_probleme VARCHAR(255) DEFAULT NULL, numero_durgence VARCHAR(20) DEFAULT NULL, autre_diplome_max VARCHAR(255) DEFAULT NULL, autre_diplome_entre VARCHAR(255) DEFAULT NULL, matricule VARCHAR(25) DEFAULT NULL, localisation VARCHAR(255) DEFAULT NULL, skills LONGTEXT DEFAULT NULL, autre_formation LONGTEXT DEFAULT NULL, code_secret VARCHAR(25) DEFAULT NULL, last_update_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_717E22E39E2EF023 (telephone1), UNIQUE INDEX UNIQ_717E22E3727A199 (telephone2), UNIQUE INDEX UNIQ_717E22E388D20D42 (adresse_email), INDEX IDX_717E22E3CCF9E01E (departement_id), INDEX IDX_717E22E3A6E44244 (pays_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE etudiant_inscris (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, classe_id INT NOT NULL, annee_academique_id INT DEFAULT NULL, can_take_releve TINYINT(1) NOT NULL, is_redoublant TINYINT(1) NOT NULL, is_add TINYINT(1) NOT NULL, is_adc TINYINT(1) NOT NULL, redouble TINYINT(1) NOT NULL, moyenne_obtenue VARCHAR(20) DEFAULT NULL, anonymat VARCHAR(255) DEFAULT NULL, moyenne_semestre1 VARCHAR(20) DEFAULT NULL, moyenne_semestre2 VARCHAR(20) DEFAULT NULL, notes_semestre1 LONGTEXT DEFAULT NULL, notes_semestre2 LONGTEXT DEFAULT NULL, notes_annuelle LONGTEXT DEFAULT NULL, is_definitif TINYINT(1) NOT NULL, credit_acquis_semestre1 INT DEFAULT NULL, credit_acquis_semestre2 INT DEFAULT NULL, INDEX IDX_D8B47842DDEAB1A3 (etudiant_id), INDEX IDX_D8B478428F5EA509 (classe_id), INDEX IDX_D8B47842B00F076 (annee_academique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE examen (id INT AUTO_INCREMENT NOT NULL, intitule VARCHAR(150) NOT NULL, code VARCHAR(5) NOT NULL, slug VARCHAR(150) NOT NULL, type VARCHAR(5) NOT NULL, pourcentage INT DEFAULT NULL, pourcentage_cc INT DEFAULT NULL, UNIQUE INDEX UNIQ_514C8FEC376925A6 (intitule), UNIQUE INDEX UNIQ_514C8FEC77153098 (code), UNIQUE INDEX UNIQ_514C8FEC989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE filiere (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, code VARCHAR(100) NOT NULL, slug VARCHAR(150) NOT NULL, lettre_pour_le_matricule VARCHAR(10) NOT NULL, UNIQUE INDEX UNIQ_2ED05D9E5E237E06 (name), UNIQUE INDEX UNIQ_2ED05D9E77153098 (code), UNIQUE INDEX UNIQ_2ED05D9E989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, code VARCHAR(100) NOT NULL, slug VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_404021BF5E237E06 (name), UNIQUE INDEX UNIQ_404021BF77153098 (code), UNIQUE INDEX UNIQ_404021BF989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE getin_user (id INT AUTO_INCREMENT NOT NULL, employe_id INT NOT NULL, username VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', is_valid TINYINT(1) NOT NULL, is_online TINYINT(1) NOT NULL, last_activity INT DEFAULT NULL, UNIQUE INDEX UNIQ_D41B1AADF85E0677 (username), UNIQUE INDEX UNIQ_D41B1AAD1B65292 (employe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE matiere_asaisir (id INT AUTO_INCREMENT NOT NULL, ec_module_id INT NOT NULL, user_id INT NOT NULL, examen_id INT NOT NULL, annee_academique_id INT NOT NULL, date_expiration DATETIME NOT NULL, is_saisie TINYINT(1) NOT NULL, is_saisi_anonym TINYINT(1) NOT NULL, INDEX IDX_E0C2983F3C651B4 (ec_module_id), INDEX IDX_E0C2983FA76ED395 (user_id), INDEX IDX_E0C2983F5C8659A (examen_id), INDEX IDX_E0C2983FB00F076 (annee_academique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, classe_id INT DEFAULT NULL, annee_academique_id INT NOT NULL, intitule VARCHAR(255) NOT NULL, code VARCHAR(100) NOT NULL, slug VARCHAR(255) NOT NULL, INDEX IDX_C2426288F5EA509 (classe_id), INDEX IDX_C242628B00F076 (annee_academique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, etudiant_inscris_id INT DEFAULT NULL, tranche_id INT DEFAULT NULL, is_paied TINYINT(1) NOT NULL, numero_quitus VARCHAR(255) NOT NULL, save_at DATETIME NOT NULL, INDEX IDX_B1DC7A1E3EB5182D (etudiant_inscris_id), INDEX IDX_B1DC7A1EB76F6B31 (tranche_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement_classe (id INT AUTO_INCREMENT NOT NULL, classe_id INT DEFAULT NULL, type_de_paiement_id INT DEFAULT NULL, montant INT NOT NULL, is_obligatoire TINYINT(1) NOT NULL, INDEX IDX_2272A868F5EA509 (classe_id), INDEX IDX_2272A86EF109C5 (type_de_paiement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pays (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, indicatif VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pourcentage_ecmodule (id INT AUTO_INCREMENT NOT NULL, ec_module_id INT NOT NULL, pourcentage_cc INT DEFAULT NULL, pourcentage_tpe INT DEFAULT NULL, pourcentage_tp INT DEFAULT NULL, pourcentage_exam INT DEFAULT NULL, UNIQUE INDEX UNIQ_E6ABF4993C651B4 (ec_module_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, code VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_E19D9AD277153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialite (id INT AUTO_INCREMENT NOT NULL, filiere_id INT NOT NULL, name VARCHAR(150) NOT NULL, code VARCHAR(100) NOT NULL, slug VARCHAR(150) NOT NULL, letter_matricule VARCHAR(5) NOT NULL, UNIQUE INDEX UNIQ_E7D6FCC15E237E06 (name), UNIQUE INDEX UNIQ_E7D6FCC177153098 (code), UNIQUE INDEX UNIQ_E7D6FCC1989D9B62 (slug), INDEX IDX_E7D6FCC1180AA129 (filiere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE synthese_modulaire (id INT AUTO_INCREMENT NOT NULL, etudiant_inscris_id INT NOT NULL, module_id INT NOT NULL, examen_id INT DEFAULT NULL, moyenne DOUBLE PRECISION DEFAULT NULL, note DOUBLE PRECISION DEFAULT NULL, credit INT DEFAULT NULL, grade VARCHAR(100) DEFAULT NULL, decision VARCHAR(100) DEFAULT NULL, points DOUBLE PRECISION DEFAULT NULL, credit_valider INT DEFAULT NULL, INDEX IDX_7A7A10853EB5182D (etudiant_inscris_id), INDEX IDX_7A7A1085AFC2B591 (module_id), INDEX IDX_7A7A10855C8659A (examen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tranche (id INT AUTO_INCREMENT NOT NULL, paiement_classe_id INT DEFAULT NULL, denomination VARCHAR(255) NOT NULL, montant INT NOT NULL, slug VARCHAR(255) NOT NULL, INDEX IDX_666758404998DB43 (paiement_classe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_de_paiement (id INT AUTO_INCREMENT NOT NULL, denomination VARCHAR(150) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE anonymat ADD CONSTRAINT FK_FC64F11823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE anonymat ADD CONSTRAINT FK_FC64F15C8659A FOREIGN KEY (examen_id) REFERENCES examen (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF962195E0F0 FOREIGN KEY (specialite_id) REFERENCES specialite (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF965200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE configuration ADD CONSTRAINT FK_A5E2A5D7B00F076 FOREIGN KEY (annee_academique_id) REFERENCES annee_academique (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499933EB5182D FOREIGN KEY (etudiant_inscris_id) REFERENCES etudiant_inscris (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499933C651B4 FOREIGN KEY (ec_module_id) REFERENCES ecmodule (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_603499934BB6D30 FOREIGN KEY (contrat_precedent_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE departement ADD CONSTRAINT FK_C1765B6398260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE ecmodule ADD CONSTRAINT FK_FA91A045AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE ecmodule ADD CONSTRAINT FK_FA91A04527634BEF FOREIGN KEY (ec_id) REFERENCES ec (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE etudiant ADD CONSTRAINT FK_717E22E3CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE etudiant ADD CONSTRAINT FK_717E22E3A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE etudiant_inscris ADD CONSTRAINT FK_D8B47842DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
        $this->addSql('ALTER TABLE etudiant_inscris ADD CONSTRAINT FK_D8B478428F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE etudiant_inscris ADD CONSTRAINT FK_D8B47842B00F076 FOREIGN KEY (annee_academique_id) REFERENCES annee_academique (id)');
        $this->addSql('ALTER TABLE getin_user ADD CONSTRAINT FK_D41B1AAD1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE matiere_asaisir ADD CONSTRAINT FK_E0C2983F3C651B4 FOREIGN KEY (ec_module_id) REFERENCES ecmodule (id)');
        $this->addSql('ALTER TABLE matiere_asaisir ADD CONSTRAINT FK_E0C2983FA76ED395 FOREIGN KEY (user_id) REFERENCES getin_user (id)');
        $this->addSql('ALTER TABLE matiere_asaisir ADD CONSTRAINT FK_E0C2983F5C8659A FOREIGN KEY (examen_id) REFERENCES examen (id)');
        $this->addSql('ALTER TABLE matiere_asaisir ADD CONSTRAINT FK_E0C2983FB00F076 FOREIGN KEY (annee_academique_id) REFERENCES annee_academique (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426288F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C242628B00F076 FOREIGN KEY (annee_academique_id) REFERENCES annee_academique (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E3EB5182D FOREIGN KEY (etudiant_inscris_id) REFERENCES etudiant_inscris (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EB76F6B31 FOREIGN KEY (tranche_id) REFERENCES tranche (id)');
        $this->addSql('ALTER TABLE paiement_classe ADD CONSTRAINT FK_2272A868F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE paiement_classe ADD CONSTRAINT FK_2272A86EF109C5 FOREIGN KEY (type_de_paiement_id) REFERENCES type_de_paiement (id)');
        $this->addSql('ALTER TABLE pourcentage_ecmodule ADD CONSTRAINT FK_E6ABF4993C651B4 FOREIGN KEY (ec_module_id) REFERENCES ecmodule (id)');
        $this->addSql('ALTER TABLE specialite ADD CONSTRAINT FK_E7D6FCC1180AA129 FOREIGN KEY (filiere_id) REFERENCES filiere (id)');
        $this->addSql('ALTER TABLE synthese_modulaire ADD CONSTRAINT FK_7A7A10853EB5182D FOREIGN KEY (etudiant_inscris_id) REFERENCES etudiant_inscris (id)');
        $this->addSql('ALTER TABLE synthese_modulaire ADD CONSTRAINT FK_7A7A1085AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE synthese_modulaire ADD CONSTRAINT FK_7A7A10855C8659A FOREIGN KEY (examen_id) REFERENCES examen (id)');
        $this->addSql('ALTER TABLE tranche ADD CONSTRAINT FK_666758404998DB43 FOREIGN KEY (paiement_classe_id) REFERENCES paiement_classe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anonymat DROP FOREIGN KEY FK_FC64F11823061F');
        $this->addSql('ALTER TABLE anonymat DROP FOREIGN KEY FK_FC64F15C8659A');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF962195E0F0');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF965200282E');
        $this->addSql('ALTER TABLE configuration DROP FOREIGN KEY FK_A5E2A5D7B00F076');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499933EB5182D');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499933C651B4');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_603499934BB6D30');
        $this->addSql('ALTER TABLE departement DROP FOREIGN KEY FK_C1765B6398260155');
        $this->addSql('ALTER TABLE ecmodule DROP FOREIGN KEY FK_FA91A045AFC2B591');
        $this->addSql('ALTER TABLE ecmodule DROP FOREIGN KEY FK_FA91A04527634BEF');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9ED5CA9E6');
        $this->addSql('ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3CCF9E01E');
        $this->addSql('ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3A6E44244');
        $this->addSql('ALTER TABLE etudiant_inscris DROP FOREIGN KEY FK_D8B47842DDEAB1A3');
        $this->addSql('ALTER TABLE etudiant_inscris DROP FOREIGN KEY FK_D8B478428F5EA509');
        $this->addSql('ALTER TABLE etudiant_inscris DROP FOREIGN KEY FK_D8B47842B00F076');
        $this->addSql('ALTER TABLE getin_user DROP FOREIGN KEY FK_D41B1AAD1B65292');
        $this->addSql('ALTER TABLE matiere_asaisir DROP FOREIGN KEY FK_E0C2983F3C651B4');
        $this->addSql('ALTER TABLE matiere_asaisir DROP FOREIGN KEY FK_E0C2983FA76ED395');
        $this->addSql('ALTER TABLE matiere_asaisir DROP FOREIGN KEY FK_E0C2983F5C8659A');
        $this->addSql('ALTER TABLE matiere_asaisir DROP FOREIGN KEY FK_E0C2983FB00F076');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426288F5EA509');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C242628B00F076');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E3EB5182D');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EB76F6B31');
        $this->addSql('ALTER TABLE paiement_classe DROP FOREIGN KEY FK_2272A868F5EA509');
        $this->addSql('ALTER TABLE paiement_classe DROP FOREIGN KEY FK_2272A86EF109C5');
        $this->addSql('ALTER TABLE pourcentage_ecmodule DROP FOREIGN KEY FK_E6ABF4993C651B4');
        $this->addSql('ALTER TABLE specialite DROP FOREIGN KEY FK_E7D6FCC1180AA129');
        $this->addSql('ALTER TABLE synthese_modulaire DROP FOREIGN KEY FK_7A7A10853EB5182D');
        $this->addSql('ALTER TABLE synthese_modulaire DROP FOREIGN KEY FK_7A7A1085AFC2B591');
        $this->addSql('ALTER TABLE synthese_modulaire DROP FOREIGN KEY FK_7A7A10855C8659A');
        $this->addSql('ALTER TABLE tranche DROP FOREIGN KEY FK_666758404998DB43');
        $this->addSql('DROP TABLE annee_academique');
        $this->addSql('DROP TABLE anonymat');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE contrat');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE ec');
        $this->addSql('DROP TABLE ecmodule');
        $this->addSql('DROP TABLE employe');
        $this->addSql('DROP TABLE etudiant');
        $this->addSql('DROP TABLE etudiant_inscris');
        $this->addSql('DROP TABLE examen');
        $this->addSql('DROP TABLE filiere');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE getin_user');
        $this->addSql('DROP TABLE matiere_asaisir');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE paiement_classe');
        $this->addSql('DROP TABLE pays');
        $this->addSql('DROP TABLE pourcentage_ecmodule');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE specialite');
        $this->addSql('DROP TABLE synthese_modulaire');
        $this->addSql('DROP TABLE tranche');
        $this->addSql('DROP TABLE type_de_paiement');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

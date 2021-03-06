<?php
session_start();	
header ('Content-type:text/html; charset=utf-8');
if(!isset($_SESSION['identification']))
{
		$_SESSION['identification']=false;
}
include("fonctions.php");
// chaîne de caractère retour qui sera compléter au fur et à mesure envoyé au client
$retour=null;
if($_SESSION['identification'])
{
// Ce fichier sert à gérer les enregistrements suite à la validation du formulaire réception des commandes par ZFW ou par BEE
		// Dans le 1er cas : réception par ZFW. On va réaliser 
				// l'enregistrement de  de la commande dans ligne_commande_retour
				// mettre à jour les stocks prévi et réelle
				// générer une facture pour ZFW
		// Dans le 1er cas, on doit gérer deux types de commandes différentes :
				// celles envoyées le fournisseur
				// celles envoyées par BEE

		// Dans le 2nd cas : réception par BEE. On va réaliser
				// l'enregistrement de  de la commande dans ligne_commande_retour
				// mettre à jour le stock de BEE
		

		// le champ destinataire nous permet de connaître le cas dans lequel on se trouve
		

				

//		erreur(print_r($_POST,true));
		// on vérifie que tous les posts sont bien présents et instanciées
		// contient les index des champs de l'entête du formulaire
		$index_post_entete=['ref_externe','nbre_ligne_cmde','expediteur','destinataire'];
		// contient les indexs des champs de la partie validation ou modification des commandes
		$index_post_commande=['marque','modele','etat','qte_recue'];
		// contient les indexs des champs de la partie répartition des produits dans les stocks
		$index_post_stockage=['ref_lieu_stockage','qte_lieu_stockage'];


// 1ère partie : vérification des données reçus ( reste à faire vérification du contenu )

		// vérification de la présence et de l'instanciation des post de l'entête
		foreach($index_post_entete as $index)
		{
				if(empty($_POST[$index]))
				{
						erreur('Erreur : tous les posts de l\'entête sont pas instanciés ou remplis');
				}
		}

		// on vérifie que le nombre lignes de commandes attendu est un entier non nul
		if(!verif_entier_non_nul($_POST['nbre_ligne_cmde']))
				{
						erreur('Erreur : le nombre de ligne de la commande doit être un entier différent de 0');
				}
		
		// on vérifie que le nombre de ligne de commandes saisies est instanciée et est un entier
		if(empty($_POST['nbre_ligne_cmde_saisie']) or !verif_entier_non_nul($_POST['nbre_ligne_cmde_saisie']))
				{
						erreur('Erreur : nbre de ligne de commandes saisies pas instanciées ou pas entier différent de 0. Il doit y avoir au moins une ligne de commandes non supplémentaires');
				}
		
		// si le destinataire est ZFW, on vérifie la présence des posts
		if($_POST['destinataire']=='ZFW')
		{
		
				//on vérifie maintenant que post contient bien les nombres de lignes de stocks saisies par ligne de commande
				for($indice_ligne_cmde=1;$indice_ligne_cmde<=$_POST['nbre_ligne_cmde_saisie'];$indice_ligne_cmde++)
				{
						if(empty($_POST['nbre_ligne_stock'.$indice_ligne_cmde]) or !verif_entier_non_nul($_POST['nbre_ligne_stock'.$indice_ligne_cmde]))
						{
								erreur('Erreur : nbre de ligne de stocks saisies pas instanciées ou pas entier différent de 0');
						}

				}
		}
		// si le nbre de ligne de commande saisie est plus grand que le nbre de ligne de commande
		if($_POST['nbre_ligne_cmde_saisie']>$_POST['nbre_ligne_cmde'])
		{
				// on vérifie que le nombre de lignes de commandes supplémentaires est instanciées et est bien un entier
				if(empty($_POST['nbre_ligne_sup']) or !verif_entier($_POST['nbre_ligne_sup']))
				{
						erreur('Erreur : nbre de ligne de commandes supplémentaires saisies pas instanciées ou pas entier');
				}
		}
		// si il y a des lignes supplémentaires, on vérifie qu'il y n'y a pas moins de lignes de commandes sais 
		if(!(empty($_POST['nbre_ligne_sup']) or !verif_entier($_POST['nbre_ligne_sup'])))
		{
				if($_POST['nbre_ligne_cmde_saisie']!=$_POST['nbre_ligne_cmde'])
				{
						erreur('Erreur : il ne peut y avoir de lignes supplémentaires lorsque le nombre de ligne de commandes est plus petit que celui de l\'entete');
				}
								
		}
		
		// vérification de la présence des post de la partie validation et modification de la commande du formulaire et de la partie réparition dans les stocks

			// des lignes de commande d'abord
			foreach(['nbre_ligne_cmde_saisie','nbre_ligne_sup'] as $nbre_type_ligne)// pour balayer les deux types de ligne : cmde et supplémentaires
			{
					// pour balayer toutes les lignes du type courant ( soit sup, soi cmde)
					for($i_row_cmde=1;$i_row_cmde<=$_POST[$nbre_type_ligne];$i_row_cmde++)
					{
							if($nbre_type_ligne=='nbre_ligne_sup')// on redéfinit l'index de ligne pour les lignes de commande sup car leur numérotation suit celle des lignes de cmde
									$i_row_cmde=$i_row_cmde+$_POST['nbre_ligne_cmde_saisie'];
							// pour vérifier l'instanciation de tous les champs de la partie validation et modification de la  commande
							foreach($index_post_commande as $index_champ_cmde)
							{	
									if($nbre_type_ligne=='nbre_ligne_sup')
											$index_champ_cmde='sup_'.$index_champ_cmde.$i_row_cmde;
									else
											$index_champ_cmde=$index_champ_cmde.$i_row_cmde;
									if(empty($_POST[$index_champ_cmde]))
									{
											erreur('Tous les posts des lignes de commandes ne sont pas instanciés ou remplis : '.$index_champ_cmde);
									}
							}

							// pour vérifier l'instanciation de tous les champs de la partie répartition dans les stocks
							for($i_row_stck=1;$i_row_stck<=$_POST['nbre_ligne_stock'.$i_row_cmde];$i_row_stck++) // on parcoure toutes les lignes de stocks de la ligne courante
							{
									foreach($index_post_stockage as $index_champ_stock)
									{	
											if($nbre_type_ligne=='nbre_ligne_sup')
													$index_champ_stock='sup_'.$index_champ_stock.$i_row_cmde.'_'.$i_row_stck;
											else
													$index_champ_stock=$index_champ_stock.$i_row_cmde.'_'.$i_row_stck;
											if(empty($_POST[$index_champ_stock]))
											{
													erreur('Tous les posts des lignes de stocks ne sont pas instanciés ou remplis : '.$index_champ_stock);
											}
									}
									
							}
					}
			}
			erreur("C'est bon");

			// des lignes supp ensuite

// a ce stade :
		//on a vérifié que tous les posts sont bien instanciées et que 
		//    nbre de ligne de commandes saisies est un entier
		//    nbres de lignes de stocks saisies est un entier
		//    nbre de ligne commandes sources est un entier
		//    nbre de ligne commandes supplémentaires est un entier
		//    qu'il y a un a bien des lignes de commandes non supplémentaires
		// 		qu'il n'y pas de lignes supplémentaires lorsque le nbre de lignes de commande saisies est plus petit que le nombre de lignes de commande source
// il reste à vérifier que ces variables aient le bon contenu

// insertion des commande saisies dans la table ligne_commande_retour
		// vérifier que la commande reçue correspond à ce qui a été commandée par BEE (pour la mise à jour du flag_ligne_validé dans la table ligne_commande_retour )
		// on va donc comparer la saisie de commandes à la commande enregistré dans la bdd. Attention cette comparaison n'est valable que pour les lignes de commandes non supplémentaires !
		// il y a deux cas :
				// 		premier cas : la commande se trouve dans cmde_fournisseur
				// 		deuxième cas : la commande se trouve dans expedition_bee
		// pour enregistrer la saisie dans la table ligne_commande_retour, on procède en plusieurs étapes 
				//		première étape : on détermine la table dans laquelle est la commande(grâce à l'entête) 
				//		deuxième étape : on compare le contenu de cette table à la saisie (afin de déterminer le flag
				//		troisième étape : on sauvegarde les données dans ligne_commande_retour
		
		//connexion à la bdd
		include("connexion.php");

		
		if($_POST['fourn_expediteur']=="four")
		{
				// requête pour voir si cette commande existe et pour récupérer la ref de la commande
				$cmde_existe='select lco.ref_cmdef as ref_cmde from cmde_fournisseur as cf,ligne_commande_origine as lco where cf.statut="ouvert" and cf.ref_cmde_externe = ? and cf.ref_cmdef = lco.ref_cmdef';
		}
		else
		{
				// requête pour vérifier que cette commande existe bien et pour récupérer la ref de la commande
				$cmde_existe='select ref_cmdef as ref_cmde from expedition_bee as eb, ligne_expedition_bee as leb where eb.statut="ouvert" and eb.ref_externe_prestataire = ? and eb.ref_expedition_bee = leb.ref_expedition_bee';
		}

		$commande_source=$bdd->prepare($cmde_existe);
		$commande_source->execute(array($_POST['fourn_ref_externe']));
		
		// on vérifie que la ref_externe existe
		$nbre_ligne_cmde_source=$commande_source->rowCount();
		if($nbre_ligne_cmde_source==0)
				erreur("La commande source n'existe pas : aucune correspondance avec la ref_externe du formulaire");
		// on récupère la ref de la commande
		$ref_commande=$commande_source->fetch();
		$ref_commande=$ref_commande['ref_cmde'];
		errors_pdo($commande_source);
		$commande_source->closeCursor();
		
		// déclaration du tableau qui va contenir les ref_ligne_commande ( forcément non supplémentaires) 
		$refs_ligne_cmde=array(); // il va servir à déterminer les éventuelles ref_ligne_commande absentes à la réception

		// on enregistre les lignes de commandes présentes à la réception ( non  supplémentaires ) ainsi que les éventuelles modifications faîtes à la saisie
		$index_val_initi_final_ligne_no_sup=['marque','modele','etat','qtee_recue'];
		for($indice_ligne_no_sup=1;$indice_ligne_no_sup<=$nbre_ligne_no_sup;$indice_ligne_no_sup++)
		{
				// on détermine si il y a une différence entre la ligne source et la ligne à la réception
				$ligne_identiques="O";// vaut "O" si il n'y a pas différences
				foreach($index_val_initi_final_ligne_no_sup as $index)
				{
						
						if($_POST[$index.'_initi'.$indice_ligne_no_sup] != $_POST[$index.'_final'.$indice_ligne_no_sup] )
						{
								$ligne_identiques="N";
						}
				}
				// requête pour récupérer la ref_produit initiale
				$ref_produit_initiale=get_id_produit($_POST['marque_initi'.$indice_ligne_no_sup],$_POST['modele_initi'.$indice_ligne_no_sup],$_POST['etat_initi'.$indice_ligne_no_sup],$bdd);
				
				// requête pour récupérer la ref_produit finale
				$ref_produit_finale=get_id_produit($_POST['marque_final'.$indice_ligne_no_sup],$_POST['modele_final'.$indice_ligne_no_sup],$_POST['etat_final'.$indice_ligne_no_sup],$bdd);

				if($_POST['fourn_expediteur']=="four")
				{		
						// pour récupérer la référence de la ligne de commande source
						$requete_ref_ligne_cmde='select ref_l_cmdef as ref_ligne_cmde from ligne_commande_origine where ref_cmdef= ? and ref_produit = ? and qte_cmdee = ?';
						
						// pour insérer les données dans la table ligne_commande_retour
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(:ref_l_cmdef,"CS",:ref_produit,:qte_recue,NOW(),:ligne_valide,"bidon",NOW(),null, :destinataire)'; 

				}
				else
				{
						// pour récupérer la référence de la ligne de commande source
						$requete_ref_ligne_cmde='select ref_l_expedition_bee as ref_ligne_cmde from ligne_expedition_bee where ref_expedition_bee= ? and ref_produit = ? and qte_expediee = ?';
						// à compléter
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(null,"EB",:ref_produit,:qte_recue,NOW(),:ligne_valide,"bidon",NOW(),:ref_l_cmdef, :destinataire)'; 
				}
				
						// commande en provenance du fournisseur directement
						// on récupère la référence de la ligne de commande source
						$pdo_ref_ligne_cmde=$bdd->prepare($requete_ref_ligne_cmde);
						$pdo_ref_ligne_cmde->execute(array(
								$ref_commande,
								$ref_produit_initiale,
								$_POST['qtee_recue_initi'.$indice_ligne_no_sup]
						));
						$ret_ref_ligne_cmde=$pdo_ref_ligne_cmde->fetch();// contient la ref de la ligne de commande source
						$ref_ligne_cmde=$ret_ref_ligne_cmde['ref_ligne_cmde'];
						array_push($refs_ligne_cmde,$ref_ligne_cmde);
						errors_pdo($pdo_ref_ligne_cmde);
						$pdo_ref_ligne_cmde->closeCursor();
						
						// on insère les données dans la table ligne_commande_retour
						$pdo_insert_lco=$bdd->prepare($requete_insert_lcr);
						$pdo_insert_lco->execute(array(
								'ref_l_cmdef'=>$ref_ligne_cmde,
								'ref_produit'=>$ref_produit_finale,
								'qte_recue'=>$_POST['qtee_recue_final'.$indice_ligne_no_sup],
								'ligne_valide'=>$ligne_identiques,
								'destinataire'=>$_POST['destinataire']
						));
					  errors_pdo($pdo_insert_lco);	
						$pdo_insert_lco->closeCursor();
				
		}
		// on enregistre les lignes absentes à la réception
		if($nbre_ligne_cmde_source!=$nbre_ligne_no_sup)
		{
				$placeholders = rtrim(str_repeat('?, ',$nbre_ligne_no_sup), ', ') ; // pour compléter la prochaine requête
				// il y a deux cas 
				if($_POST['fourn_expediteur']=="four") // si la commande est expédiée par le fournisseur directement
				{
						$req_slct_ref_ligne_abste = 'select lco.ref_l_cmdef as ref_ligne_cmde from ligne_commande_origine as lco, cmde_fournisseur as cf  where lco.ref_l_cmdef not in ('.$placeholders.') and lco.ref_cmdef = cf.ref_cmdef and cf.ref_cmde_externe = ?';
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(:ref_ligne_cmde,"CS",null,0,NOW(),"N","bidon",NOW(),null,:destinataire)'; 

				}
				else // si la commande est expédiée par BEE
				{
						$req_slct_ref_ligne_abste='select leb.ref_l_expedition_bee as ref_ligne_cmde from ligne_expedition_bee as leb, expedition_bee as eb where leb.ref_l_expedition_bee not in ('.$placeholders.') and leb.ref_expedition_bee =eb.ref_expedition_bee and eb.ref_externe_prestataire = ?';
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(null,"EB",null,0,NOW(),"N","bidon",NOW(),:ref_ligne_cmde,:destinataire)'; 
				}	
				$pdo_slct_ref_ligne_abste = $bdd->prepare($req_slct_ref_ligne_abste);
				$pdo_slct_ref_ligne_abste->execute(array_merge($refs_ligne_cmde,array($_POST['fourn_ref_externe'])));
				// on prépare la requête qui va insérer les données 
				$pdo_insert_lcr=$bdd->prepare($requete_insert_lcr);
				// tant qu'il y a des ref lignes absentes, on les insère dans ligne_commande_origine
				while($ret_slct_ref_ligne_abste = $pdo_slct_ref_ligne_abste->fetch())
				{
					$pdo_insert_lcr->execute(array(
							'ref_ligne_cmde'=>$ret_slct_ref_ligne_abste['ref_ligne_cmde'],
							'destinataire'=>$_POST['destinataire']
					));
				}
				errors_pdo($pdo_slct_ref_ligne_abste);
				errors_pdo($pdo_insert_lcr);
				$pdo_slct_ref_ligne_abste->closeCursor();
				$pdo_insert_lcr->closeCursor();
				$retour='la commande source est différence de la commande reçue  : il y a des lignes en moins.';

		}

		// on enregistre les lignes supplémentaires à la réception
		if($_POST['nbre_lignes_suppl']!=0)
		{

				if($_POST['fourn_expediteur']=="four") // si la commande est expédiée par le fournisseur directement
				{
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(null,"CS",:ref_produit,:qte_recue,NOW(),"N","bidon",NOW(),null, :destinataire)'; 

				}
				else // si la commande est expédiée par BEE
				{
						$requete_insert_lcr='insert into ligne_commande_retour(ref_l_cmdef,origine_ligne,ref_produit,qte_recue,date_validation,flag_ligne_validee,user_id,date_heure_maj,ref_l_expedition_bee, destinataire) VALUES(null,"EB",:ref_produit,:qte_recue,NOW(),"N","bidon",NOW(),null, :destinataire)'; 
				}
				
				for($indice_ligne_supp=0;$indice_ligne_supp<count($indices_lignes_supp);$indice_ligne_supp++)
				{
						// requête pour récupérer la ref_produit
						$ref_produit=get_id_produit($_POST['fourn_marque'.$indices_lignes_supp[$indice_ligne_supp]],$_POST['fourn_modele'.$indices_lignes_supp[$indice_ligne_supp]],$_POST['fourn_etat'.$indices_lignes_supp[$indice_ligne_supp]],$bdd);
						$pdo_requete_insert_lcr = $bdd->prepare($requete_insert_lcr);
						$pdo_requete_insert_lcr->execute(array(
								'ref_produit'=>$ref_produit,
								'qte_recue'=>$_POST['fourn_qte_recue'.$indices_lignes_supp[$indice_ligne_supp]],
								'destinataire'=>$_POST['destinataire']

						));
						errors_pdo($pdo_requete_insert_lcr);
						$pdo_requete_insert_lcr->closeCursor();
				}
				
				
		}
// après insertion des données dans la ligne_commande_retour, on met à jour le statut de la commande qui passe de ouvert à fermé
				if($_POST['fourn_expediteur']=="four") // si la commande est expédiée par le fournisseur directement
				{
						$req_maj_statut_commande='update cmde_fournisseur set statut="ferme" where ref_cmdef = ?'; 
				}
				else // si la commande est expédiée par BEE
				{
						$req_maj_statut_commande='update expedition_bee set statut="ferme" where ref_expedition_bee = ?'; 
				}
				$pdo_maj_statut_commande=$bdd->prepare($req_maj_statut_commande);
				$pdo_maj_statut_commande->execute(array($ref_commande));
		
// on va maintenant faire la maj des stocks
		// il y a deux cas possible dans la mise à jour de ces stocks :
				// soit le destinataire est ZFW et dans ce cas on met à jour les stocks position_zfw_previ et position_zfw_reelle
				// soit le destinataire est BEE et dans ce cas on met à jour stock_bee

				if($_POST['destinataire']=='ZFW')// si le destinataire est zfw, on met à jour position_zfw_previ et position_zfw_relle
				{
						for($indice_row=1;$indice_row<=$_POST['nbre_ligne_commande_saisie'];$indice_row++ )
						{
						$nbre_ligne_stock = 'nb_ligne_stk'.$indice_row;
						$ref_produit=get_id_produit($_POST['fourn_marque'.$indice_row],$_POST['fourn_modele'.$indice_row],$_POST['fourn_etat'.$indice_row],$bdd);
								for($indice_ligne_stock=1;$indice_ligne_stock <=$_POST[$nbre_ligne_stock]; $indice_ligne_stock++)
								{
										// on construit les index pour avoir accés à la ref_lieu_stockage et à la quantité
										$index_ref_lieu_stockage='fourn_ref_lieu_stockage'.$indice_row.'_'.$indice_ligne_stock;
										$index_qte_lieu_stockage='fourn_qte_lieu_stockage'.$indice_row.'_'.$indice_ligne_stock;

		// maj de position_zfw_previ d'abord

										// on voit si la ref produit en question existe
										$req_produit_existe='select qte, date_archivage as max_date from position_zfw_previ where ref_lieu_stockage = ? and ref_produit = ? order by date_archivage desc limit 1';
										$pdo_produit_existe=$bdd->prepare($req_produit_existe);
										$pdo_produit_existe->execute(array(
												$_POST[$index_ref_lieu_stockage],
												$ref_produit
										));
										// requete pour insérer la nouvelle ligne de stocks dans position_zfw_previ
										$req_insert_stock = 'insert into position_zfw_previ (date_archivage, ref_lieu_stockage, ref_produit, qte, user_id, date_heure_maj) values(NOW(),:ref_lieu_stockage,:ref_produit,:qte,"bidon",NOW())';
												$pdo_insert_stock = $bdd->prepare($req_insert_stock);
										if($pdo_produit_existe->rowCount()!=0)
										// si elle existe, on insère une nouvelle ligne dont la quantité dépend de l'autre ligne avec la même ref_produit, la me ref_stock et de la date la + récente
										{
												// on récupère la qte existante et on la met à jour
												$row_produit_existe=$pdo_produit_existe->fetch();
												$qte_produit_existe=$row_produit_existe['qte'];
												$new_qte=$qte_produit_existe+$_POST[$index_qte_lieu_stockage];
												errors_pdo($pdo_produit_existe);	
												$pdo_produit_existe->closeCursor();
												$pdo_insert_stock->execute(array(
														'ref_lieu_stockage' => $_POST[$index_ref_lieu_stockage] ,
														'ref_produit' => $ref_produit,
														'qte' => $new_qte
												));
										}
										else
										// si elle existe pas, on l'ajoute
										{
												$pdo_insert_stock->execute(array(
														'ref_lieu_stockage' => $_POST[$index_ref_lieu_stockage] ,
														'ref_produit' => $ref_produit,
														'qte' => $_POST[$index_qte_lieu_stockage]
												));
										}
										errors_pdo($pdo_insert_stock);
										$pdo_insert_stock->closeCursor();
		// maj de position_zfw_reelle ensuite

										// on voit si la ref produit en question existe
										$req_produit_existe='select qte, date_archivage as max_date from position_zfw_reelle where ref_lieu_stockage = ? and ref_produit = ? order by date_archivage desc limit 1';
										$pdo_produit_existe=$bdd->prepare($req_produit_existe);
										$pdo_produit_existe->execute(array(
												$_POST[$index_ref_lieu_stockage],
												$ref_produit));
										// requete pour insérer la nouvelle ligne de stocks dans position_zfw_previ
										$req_insert_stock = 'insert into position_zfw_reelle (date_archivage, ref_lieu_stockage, ref_produit, qte, user_id, date_heure_maj) values(NOW(),:ref_lieu_stockage,:ref_produit,:qte,"bidon",NOW())';
												$pdo_insert_stock = $bdd->prepare($req_insert_stock);
										if($pdo_produit_existe->rowCount()!=0)
										// si elle existe, on insère une nouvelle ligne dont la quantité dépend de l'autre ligne avec la même ref_produit, la me ref_stock et de la date la + récente
										{
												// on récupère la qte existante et on la met à jour
												$row_produit_existe=$pdo_produit_existe->fetch();
												$qte_produit_existe=$row_produit_existe['qte'];
												$new_qte=$qte_produit_existe+$_POST[$index_qte_lieu_stockage];
												errors_pdo($pdo_produit_existe);
												$pdo_produit_existe->closeCursor();
												$pdo_insert_stock->execute(array(
														'ref_lieu_stockage' => $_POST[$index_ref_lieu_stockage] ,
														'ref_produit' => $ref_produit,
														'qte' => $new_qte
												));
										}
										else
										// si elle existe pas, on l'ajoute
										{
												$pdo_insert_stock->execute(array(
														'ref_lieu_stockage' => $_POST[$index_ref_lieu_stockage] ,
														'ref_produit' => $ref_produit,
														'qte' => $_POST[$index_qte_lieu_stockage]
												));
										}
										errors_pdo($pdo_insert_stock);
										$pdo_insert_stock->closeCursor();
								}

						}
				}
				else if($_POST['destinataire']=='BEE')//si le destinataire est BEE, on met à jour stock_bee
				{
						for($indice_row=1;$indice_row<=$_POST['nbre_ligne_commande_saisie'];$indice_row++ )
						{
								$ref_produit=get_id_produit($_POST['fourn_marque'.$indice_row],$_POST['fourn_modele'.$indice_row],$_POST['fourn_etat'.$indice_row],$bdd);

								// on voit si la ref produit en question existe
								$req_produit_existe='select qte, date_archivage as max_date from stock_bee where ref_produit = ? order by date_archivage desc limit 1';
								$pdo_produit_existe=$bdd->prepare($req_produit_existe);
								$pdo_produit_existe->execute(array(
										$ref_produit
								));
								// requete pour insérer la nouvelle ligne de stocks dans position_zfw_previ
								$req_insert_stock = 'insert into stock_bee (date_archivage, ref_produit, qte, user_id, date_heure_maj) values(NOW(),:ref_produit,:qte,"bidon",NOW())';
										$pdo_insert_stock = $bdd->prepare($req_insert_stock);
								if($pdo_produit_existe->rowCount()!=0)
								// si elle existe, on insère une nouvelle ligne dont la quantité dépend de l'autre ligne avec la même ref_produit, la me ref_stock et de la date la + récente
								{
										// on récupère la qte existante et on la met à jour
										$row_produit_existe=$pdo_produit_existe->fetch();
										$qte_produit_existe=$row_produit_existe['qte'];
										$new_qte=$qte_produit_existe+$_POST['fourn_qte_recue'.$indice_row];
										errors_pdo($pdo_produit_existe);	
										$pdo_produit_existe->closeCursor();
										$pdo_insert_stock->execute(array(
												'ref_produit' => $ref_produit,
												'qte' => $new_qte
										));
								}
								else
								// si elle existe pas, on l'ajoute
								{
										$pdo_insert_stock->execute(array(
												'ref_produit' => $ref_produit,
												'qte' => $_POST['fourn_qte_recue'.$indice_row]
										));
								}
								errors_pdo($pdo_insert_stock);
								$pdo_insert_stock->closeCursor();
						}
				}


// on va maintenant générrer une facture pour ZFW 
				
				if($_POST['destinataire']=='ZFW')
				{
						// il y a deux cas :
								// soit la commande est envoyé par BEE
								// soit la commande est envoyé par le fournisseur
						

/* REste à faire le deuxième cas et à gérer les cas sans demande */






						$req_elem_factu = "select lcr.ref_l_cmdef_ret as ref_ligne_cmdef, dz.ref_canal_distrib as canal_distrib, lcr.qte_recue as qte_recue, lcr.ref_produit as ref_produit_recue from cmde_fournisseur cf, ligne_commande_origine as lco, ligne_commande_retour as lcr, demande_zfw as dz  where cf.ref_cmde_externe = ? and	cf.ref_cmdef = lco.ref_cmdef and lco.ref_demande_zfw = dz.ref_dde_zfw and lco.ref_l_cmdef = lcr.ref_l_cmdef";
						$pdo_elem_factu = $bdd->prepare($req_elem_factu);
						$pdo_elem_factu->execute(array(
								$_POST['fourn_ref_externe']
						));
						while($ret_elem_factu=$pdo_elem_factu->fetch())
						{
								$prix_produit = retour_select('select prix from catalogue_produit where code_devise=? and ref_canal_distrib= ? and ref_produit = ? and date_validite>=NOW()',array('USD',$ret_elem_factu['canal_distrib'],$ret_elem_factu['ref_produit_recue']),'prix',$bdd);
								$montant_totale_lcr=$ret_elem_factu['qte_recue']*$prix_produit;
								if($prix_produit==null)
								{
										erreur('Erreur : impossible de terminer la facturation car impossible de trouver le prix catalogue de ce produit : '.$ret_elem_factu['ref_produit_recue'].' . Ajouter le prix au catalogue et faîte la facturation manuelle de cette ref_cmde_externe : '.$_POST['fourn_ref_externe']);
								}
								// on enregistre les élements de facturation
								$req_saving_factu='insert into facturation_zfw(ref_ligne_cmdef,montant_a_payer, montant_regle, code_devise, user_id, flag_valide, date_heure_maj) values(?,?,0,"USD","bidon","N",NOW())';
								$pdo_saving_factu=$bdd->prepare($req_saving_factu);
								$pdo_saving_factu->execute(array(
										$ret_elem_factu['ref_ligne_cmdef'],
										$montant_totale_lcr
								));
								errors_pdo($pdo_saving_factu);
								$pdo_saving_factu->closeCursor();
						}
						errors_pdo($pdo_elem_factu);
						$pdo_elem_factu->closeCursor();
						

				}
		retour_ajax($retour.'Commande enregistrée');	
		

// mettre à jour le statut de la commande source et la date de dernière modif: ouvert -> ferme

// vérifier que la demande est satisfaite si cela fait suite à une demande


// faire la réparition dans les stocks

// si erreur, faire en sorte que rien ne se passe
		
/* liste des post à vérifier 
    [fourn_ref_externe] => ref_bidon_2_sans_demande
    [fourn_nbre_ligne_cmde] => 2
    [fourn_expediteur] => four
    [fourn_ref_demande] => pas dem
    [fourn_marque1] => BLACKBERRY
    [fourn_modele1] => Blackberry Q5 - 8 Go - Noir - Débloqué
    [fourn_etat1] => Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état, contrôlé et nettoyé - Po
    [fourn_qte_recue1] => 8
    [fourn_ref_lieu_stockage1_1] => 1
    [fourn_qte_lieu_stockage1_1] => 56
    [fourn_marque2] => APPLE
    [fourn_modele2] => Apple Iphone 4 32 Go - Blanc - Débloqué
    [fourn_etat2] => Très Bon etat - Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état - Contrôl
    [fourn_qte_recue2] => 80
    [fourn_ref_lieu_stockage2_1] => 1
    [fourn_qte_lieu_stockage2_1] => 45
    [nb_ligne_stk1] => 1
    [nb_ligne_stk2] => 1
    [ligne1] => ligne1
    [marque1initi] => BLACKBERRY
    [marque1final] => BLACKBERRY
    [modele1initi] => Blackberry Q5 - 8 Go - Noir - Débloqué
    [modele1final] => Blackberry Q5 - 8 Go - Noir - Débloqué
    [etat1initi] => Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état, contrôlé et nettoyé - Po
    [etat1final] => Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état, contrôlé et nettoyé - Po
    [qtee_recue1initi] => 8
    [qtee_recue1final] => 8
    [ligne2] => ligne2
    [marque2initi] => APPLE
    [marque2final] => APPLE
    [modele2initi] => Apple Iphone 4 32 Go - Blanc - Débloqué
    [modele2final] => Apple Iphone 4 32 Go - Blanc - Débloqué
    [etat2initi] => Très Bon etat - Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état - Contrôl
    [etat2final] => Très Bon etat - Vendeur pro - Garantie 3 mois - Débloqué tout opérateur - En très bon état - Contrôl
    [qtee_recue2initi] => 80
    [qtee_recue2final] => 80
    [nbre_ligne_commande_saisie] => 2
		[nbre_lignes_suppl] => 0
*/

}
else
{		
		erreur("Erreur : vous n'êtes pas authentifié");
}



?>

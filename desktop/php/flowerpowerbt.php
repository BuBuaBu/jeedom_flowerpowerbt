<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'flowerpowerbt');
$eqLogics = eqLogic::byType('flowerpowerbt');

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
   <div class="eqLogicThumbnailContainer">
     <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
     <center>
       <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
     </center>
     <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
   </div>
 </div>


        <legend><i class="fa fa-table"></i>  {{Mes Flower Power}}
        </legend>
		<div class="eqLogicThumbnailContainer">

                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff ; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                    echo "<center>";
                    echo '<img src="' . $eqLogic->getConfiguration('avatar_url') . '" height="105" width="95" />';
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
                ?>
            </div>
    </div>


    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class="row">
            <div class="col-sm-6">
                <form class="form-horizontal">
            <fieldset>
                <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i>  {{Général}}
                <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                </legend>
                <div class="form-group">
                    <label class="col-md-2 control-label">{{Flower Power}}</label>
                    <div class="col-md-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement flowerpowerbt}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label" >{{Objet parent}}</label>
                    <div class="col-md-3">
                        <select class="form-control eqLogicAttr" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (object::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">{{Catégorie}}</label>
                    <div class="col-md-8">
                        <?php
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline">';
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>';
                        }
                        ?>

                    </div>
                </div>
                <div class="form-group">
                <label class="col-sm-2 control-label" ></label>
                <div class="col-sm-9">
                 <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
                  <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
                </div>
                </div>
                <div class="form-group expertModeVisible">
                    <label class="col-md-2 control-label">{{Délai max entre 2 messages}}</label>
                    <div class="col-md-8">
                        <input class="eqLogicAttr form-control" data-l1key="timeout" placeholder="Délai maximum autorisé entre 2 messages (en mn)"/>
                    </div>
                </div>
                            <div class="form-group">
                    <label class="col-sm-2 control-label">{{Commentaire}}</label>
                    <div class="col-md-8">
                        <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
                    </div>
                </div>

            </fieldset>

        </form>
        </div>

                <div id="infoNode" class="col-sm-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Configuration}}</legend>

                        <div class="form-group">
                                            		<label class="col-md-2 control-label">{{ID du capteur}}</label>
                                            		<div class="col-md-3">
                                            		 <span class="eqLogicAttr" data-l1key="configuration" data-l2key="sensor_serial"></span>
                                            		</div>

                                            		<label class="col-md-2 control-label">{{Dernière Collecte}}</label>
                                            		<div class="col-md-3">
                                                	<span class="eqLogicAttr" data-l1key="configuration" data-l2key="updatetime"></span>
                                            		</div>

                                        	</div>

                                          <div id="infoSketch" class="form-group">
                                          <label class="col-md-2 control-label">{{Nom de la plante}}</label>
                                       <div class="col-md-3">
                                        <span class="eqLogicAttr" data-l1key="configuration" data-l2key="plant_nickname"></span>
                                          </div>

                                                <label class="col-md-2 control-label">{{Nom du capteur}}</label>
                                          <div class="col-md-3">
                                            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="nickname"></span>
                                          </div>

                                    </div>

                                        	<div class="form-group">
                                            		<label class="col-md-2 control-label">{{Couleur}}</label>
                                            		<div class="col-md-3">
                                                	<span class="eqLogicAttr" data-l1key="configuration" data-l2key="color"></span>
                                            		</div>

                                            		<label class="col-md-2 control-label">{{Batterie}}</label>
                                            		<div class="col-md-3">
                                            		 <span class="eqLogicAttr" data-l1key="configuration" data-l2key="batteryStatus"></span>
                                            		</div>
                                        	</div>

                                          <div class="form-group">
                                                     <label class="col-md-3 control-label">{{Commande Alerte}}</label>
                                                      <div class="col-md-6">
                                                        <div class="input-group">
                                                            <input type="text"  class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="alert" />
                                                            <span class="input-group-btn">
                                                                <a class="btn btn-default cursor" title="Rechercher une commande" id="bt_selectMailCmd"><i class="fa fa-list-alt"></i></a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                          <div class="form-group">
                                            		<div style="text-align: center">
                        							<span id="avatar_url" class="eqLogicAttr" data-l1key="configuration" data-l2key="avatar_url" style="display:none;"></span>
                        							</div>
                                        	</div>
                                          <div class="form-group">
                                            		<div style="text-align: center">
                        							<img name="icon_visu" src="" width="160" height="200"/>
                        							</div>
                                        	</div>
                    </fieldset>
                </form>
            </div>
        </div>

	<legend>{{Capteurs}}</legend>

        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 250px;">{{Valeur}}</th>
                    <th style="width: 200px;">{{Paramètres}}</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php include_file('desktop', 'flowerpowerbt', 'js', 'flowerpowerbt'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
	$( "#avatar_url" ).change(function(){
			var text = $("#avatar_url").text();
			//$("#icon_visu").attr('src',text);
			document.icon_visu.src=text;
 });
</script>

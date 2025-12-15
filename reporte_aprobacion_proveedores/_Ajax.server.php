    <?php

    // NO MODIFICAR ESTA LÍNEA
    require("_Ajax.comun.php");

    /* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
    /* FUNCIONES AJAX DEL MÓDULO */
    /* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
    //para ver errores en el navegador
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    function genera_formulario_pedido($sAccion = 'nuevo', $aForm = '')
    {
        global $DSN_Ifx, $DSN;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $oIfx = new Dbo;
        $oIfx->DSN = $DSN_Ifx;
        $oIfx->Conectar();

        $oReturn = new xajaxResponse();

        $idempresa  = $_SESSION['U_EMPRESA'];

        // LISTA EMPRESA
        $sql = "SELECT empr_cod_empr, empr_nom_empr FROM saeempr";
        $lista_empr = lista_boostrap_func($oIfx, $sql, $idempresa, 'empr_cod_empr', 'empr_nom_empr');

        $html = '
        <h3>APROBACION DE PROVEEDORES</h3>

        <div class="row">

            <div class="col-md-12">

                <div class="btn-group" style="margin-top:10px; margin-bottom:15px; margin-left:25px;">
                    <div class="btn btn-primary btn-sm" onclick="genera_formulario();">
                        <span class="glyphicon glyphicon-file"></span> Nuevo
                    </div>
                    <div class="btn btn-primary btn-sm" onclick="guardar();">
                        <span class="glyphicon glyphicon-floppy-disk"></span> Guardar
                    </div>
                </div>

                <div class="form-row" style="margin-left:10px;">

                    <div class="col-md-3">
                        <label>* Empresa:</label>
                        <select id="empresa" name="empresa" class="form-control input-sm">
                            <option value="">Seleccione una opción...</option>' 
                            . $lista_empr . 
                        '</select>
                    </div>

                    <div class="col-md-3">
                        <label>Fecha Inicio</label>
                        <input type="date" id="fecha_ini" name="fecha_ini"
                            value="" class="form-control input-sm">
                    </div>

                    <div class="col-md-3">
                        <label>Fecha Fin</label>
                        <input type="date" id="fecha_fin" name="fecha_fin"
                            value="" class="form-control input-sm">
                    </div>

                    <div class="col-md-4">
                        <label>Proveedores:</label>
                        <div class="form-group input-group">
                            <input type="hidden" id="proveedor_codigo" name="proveedor_codigo">
                            <input type="text" id="proveedor_nombre" name="proveedor_nombre"
                                class="form-control input-sm"
                                placeholder="ESCRIBA EL PROVEEDOR Y PRESIONE ENTER O F4">
                            <span class="input-group-addon primary" onclick="autocompletar_proveedor_btn()">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div>

                </div>

            </div>

            <div class="col-md-12"><br></div>

            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="btn btn-primary btn-sm" onclick="limpiarConsulta(); consultar();" 
                    style="width:100%; margin-top:10px;">
                    <span class="glyphicon glyphicon-search"></span> Consultar
                </div>
            </div>

        </div>
        ';


        $oReturn->assign("divFormularioCabecera", "innerHTML", $html);
        return $oReturn;
    }

    function consultar($aForm = '')
    {
        global $DSN_Ifx;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // ==========================
        // CONEXIONES A INFORMIX
        // ==========================
        $oIfx  = new Dbo;  
        $oIfx->DSN  = $DSN_Ifx; 
        $oIfx->Conectar();  

        $oAux  = new Dbo;  
        $oAux->DSN  = $DSN_Ifx; 
        $oAux->Conectar();  

        $oReturn = new xajaxResponse();

        // ==========================
        // VARIABLES
        // ==========================
        $empresa   = $aForm['empresa'];

        $proveedor = $aForm['proveedor_codigo'];

        $fechaIni = $aForm['fecha_ini'];
        $fechaFin = $aForm['fecha_fin'];


        // echo '<p>Proveedor: '.$proveedor;
        // exit;
        

        // ==========================
        // FILTROS
        // ==========================
        $proveedor = $aForm['proveedor_codigo'];

        $condProveedor = "";
        $condEstado    = " AND p.clpv_est_clpv <> 'A' ";

        if ($proveedor !== "" && is_numeric($proveedor)) {
            $condProveedor = " AND p.clpv_cod_clpv = $proveedor ";
            $condEstado = ""; 
        }
        //filtro de fechas
        $condFecha = "";

        if ($proveedor == "") {

            // si no selecciona proveedor aplicamos fechas
            if ($fechaIni != "" && $fechaFin != "") {
                $condFecha = " AND p.clpv_fec_des BETWEEN '$fechaIni' AND '$fechaFin' ";
            } else if ($fechaIni != "") {
                $condFecha = " AND p.clpv_fec_des >= '$fechaIni' ";
            } else if ($fechaFin != "") {
                $condFecha = " AND p.clpv_fec_des <= '$fechaFin' ";
            }

        }

        // ==========================
        // CONSULTA PRINCIPAL
        // ==========================
        $sql = "
            SELECT 
            p.clpv_cod_clpv,
            p.clpv_ruc_clpv,
            p.clpv_nom_clpv,
            p.clpv_cod_sucu,
            s.sucu_nom_sucu,
            p.grpv_cod_grpv,
            p.clpv_cod_cact,
            p.clpv_cod_zona,
            p.clpv_cod_tprov,
            p.clpv_cod_fpagop,
            p.clpv_cod_tpago

        FROM saeclpv p
        LEFT JOIN saesucu s 
            ON s.sucu_cod_empr = p.clpv_cod_empr
        AND s.sucu_cod_sucu = p.clpv_cod_sucu

        WHERE p.clpv_clopv_clpv = 'PV'
        AND p.clpv_cod_empr = $empresa
        $condProveedor
        $condEstado
        $condFecha
        ORDER BY p.clpv_nom_clpv

        ";

        //     $oReturn->alert("SQL final:\n\n".$sql);
         // return $oReturn;


        // ==========================
        // ARMADO DE TABLA
        // ==========================
        $html = '
            <div class="col-md-12">
            <table id="tbclientes" class="table table-bordered table-hover table-striped table-condensed" style="margin-top: 30px">
                        <thead>
                            <tr>
                                <th colspan="30"><h6>LISTA DE PEDIDOS FINALIZADOS</h6></th>
                            </tr>

                    <tr>
                        <th class="success" style="color: #00859B; font-weight: bold">N.-</th>
                        <th class="success" style="color: #00859B; font-weight: bold">CÓDIGO</th>
                        <th class="success" style="color: #00859B; font-weight: bold">RUC</th>
                        <th class="success" style="color: #00859B; font-weight: bold">NOMBRE</th>
                        <th class="success" style="color: #00859B; font-weight: bold">SUCURSAL</th>

                        <th class="success" style="color: #00859B; font-weight: bold">GRUPO</th>
                        <th class="success" style="color: #00859B; font-weight: bold">FLUJO CAJA</th>
                        <th class="success" style="color: #00859B; font-weight: bold">ZONA</th>
                        <th class="success" style="color: #00859B; font-weight: bold">TIPO PROV.</th>
                        <th class="success" style="color: #00859B; font-weight: bold">FORMA PAGO</th>
                        <th class="success" style="color: #00859B; font-weight: bold">DESTINO PAGO</th>

                        <th class="success" style="color: #00859B; font-weight: bold">TELÉFONO</th>
                        <th class="success" style="color: #00859B; font-weight: bold">CORREO</th>
                        <th class="success" style="color: #00859B; font-weight: bold">DIRECCIÓN</th>

                        <th class="success"> 
                            <input type="checkbox" onclick="marcar(this);"> 
                        </th>
                    </tr>
                </thead>
                <tbody>
        ';

        // ==========================
        // EJECUTAR CONSULTA
        // ==========================
        if ($oIfx->Query($sql) && $oIfx->NumFilas() > 0) {
            $contador = 1;
            do {
                // ============================================================
                // DATOS DEL PROVEEDOR
                // ============================================================
                $codigo  = trim($oIfx->f('clpv_cod_clpv'));
                $ruc     = trim($oIfx->f('clpv_ruc_clpv'));
                $nombre  = trim($oIfx->f('clpv_nom_clpv'));

                $sucuCod = trim($oIfx->f('clpv_cod_sucu'));
                $sucursal_nombre = trim($oIfx->f('sucu_nom_sucu'));

                // ============================================================
                // CONSULTAS RELACIONADAS
                // ============================================================

                // -------- TELEFONO ----------
                $telefono = "";
                $sql_tel = "
                    SELECT tlcp_tlf_tlcp
                    FROM saetlcp
                    WHERE tlcp_cod_empr = $empresa
                    AND tlcp_cod_sucu = $sucuCod
                    AND tlcp_cod_clpv = $codigo
                    LIMIT 1
                ";
                if ($oAux->Query($sql_tel) && $oAux->NumFilas() > 0) {
                    $telefono = trim($oAux->f('tlcp_tlf_tlcp'));
                }

                // -------- CORREO ----------
                $correo = "";
                $sql_cor = "
                    SELECT emai_ema_emai
                    FROM saeemai
                    WHERE emai_cod_empr = $empresa
                    AND emai_cod_sucu = $sucuCod
                    AND emai_cod_clpv = $codigo
                    LIMIT 1
                ";
                if ($oAux->Query($sql_cor) && $oAux->NumFilas() > 0) {
                    $correo = trim($oAux->f('emai_ema_emai'));
                }

                // -------- DIRECCIÓN ----------
                $direccion = "";
                $sql_dir = "
                    SELECT dire_dir_dire
                    FROM saedire
                    WHERE dire_cod_empr = $empresa
                    AND dire_cod_sucu = $sucuCod
                    AND dire_cod_clpv = $codigo
                    LIMIT 1
                ";
                if ($oAux->Query($sql_dir) && $oAux->NumFilas() > 0) {
                    $direccion = trim($oAux->f('dire_dir_dire'));
                }

                // ============================================================
                // CAMPOS RELACIONADOS
                // ============================================================

                // GRUPO
                $grupo = "";
                $codigoGrupo = trim($oIfx->f('grpv_cod_grpv'));

                if ($codigoGrupo != "") {

                    $sql_g = "
                        SELECT grpv_nom_grpv
                        FROM saegrpv
                        WHERE grpv_cod_empr = $empresa
                        AND grpv_cod_modu = 4
                        AND grpv_cod_grpv = '$codigoGrupo'
                        LIMIT 1
                    ";

                    if ($oAux->Query($sql_g) && $oAux->NumFilas() > 0) {
                        $grupo = trim($oAux->f('grpv_nom_grpv'));
                    }
                }


                // FLUJO CAJA
                $flujo = "";
                if ($oIfx->f('clpv_cod_cact') != "") {
                    $sql_f = "
                        SELECT cact_nom_cact
                        FROM saecact
                        WHERE cact_cod_empr = $empresa
                        AND cact_cod_cact = '{$oIfx->f('clpv_cod_cact')}'
                    ";
                    if ($oAux->Query($sql_f) && $oAux->NumFilas() > 0) {
                        $flujo = trim($oAux->f('cact_nom_cact'));
                    }
                }

                // ZONA
                $zona = "";
                if ($oIfx->f('clpv_cod_zona') != "") {
                    $sql_z = "
                        SELECT zona_nom_zona
                        FROM saezona
                        WHERE zona_cod_empr = $empresa
                        AND zona_cod_zona = '{$oIfx->f('clpv_cod_zona')}'
                    ";
                    if ($oAux->Query($sql_z) && $oAux->NumFilas() > 0) {
                        $zona = trim($oAux->f('zona_nom_zona'));
                    }
                }

                // TIPO PROVEEDOR
                $tipoProv = "";
                if ($oIfx->f('clpv_cod_tprov') != "") {
                    $sql_t = "
                        SELECT tprov_des_tprov
                        FROM saetprov
                        WHERE tprov_cod_empr = $empresa
                        AND tprov_cod_tprov = '{$oIfx->f('clpv_cod_tprov')}'
                    ";
                    if ($oAux->Query($sql_t) && $oAux->NumFilas() > 0) {
                        $tipoProv = trim($oAux->f('tprov_des_tprov'));
                    }
                }

                // FORMA DE PAGO
                $formaPago = "";
                if ($oIfx->f('clpv_cod_fpagop') != "") {
                    $sql_fp = "
                        SELECT fpagop_des_fpagop
                        FROM saefpagop
                        WHERE fpagop_cod_empr = $empresa
                        AND fpagop_cod_fpagop = '{$oIfx->f('clpv_cod_fpagop')}'
                    ";
                    if ($oAux->Query($sql_fp) && $oAux->NumFilas() > 0) {
                        $formaPago = trim($oAux->f('fpagop_des_fpagop'));
                    }
                }

                // DESTINO DE PAGO
                $destino = "";
                if ($oIfx->f('clpv_cod_tpago') != "") {
                    $sql_dp = "
                        SELECT tpago_des_tpago
                        FROM saetpago
                        WHERE tpago_cod_empr = $empresa
                        AND tpago_cod_tpago = '{$oIfx->f('clpv_cod_tpago')}'
                    ";
                    if ($oAux->Query($sql_dp) && $oAux->NumFilas() > 0) {
                        $destino = trim($oAux->f('tpago_des_tpago'));
                    }
                }

                // ============================================================
                // IMPRIMIR FILA
                // ============================================================
                $html .= "
                    <tr>
                        <td>$contador</td>
                        <td>$codigo</td>
                        <td>$ruc</td>
                        <td>$nombre</td>
                        <td>$sucursal_nombre</td>

                        <td>$grupo</td>
                        <td>$flujo</td>
                        <td>$zona</td>
                        <td>$tipoProv</td>
                        <td>$formaPago</td>
                        <td>$destino</td>

                        <td>$telefono</td>
                        <td>$correo</td>
                        <td>$direccion</td>

                        <td align='center'>
                            <input type='checkbox' name='prov_$codigo' value='$codigo'>
                        </td>
                    </tr>
                ";
                $contador++;

            } while ($oIfx->SiguienteRegistro());

        } else {
            $html .= '<tr><td colspan="40" style="text-align:center;">NO EXISTEN DATOS</td></tr>';
        }

        $html .= "</tbody></table>";

        $oReturn->assign("divFormularioDetalle", "innerHTML", $html);
        $oReturn->script("init()");
        return $oReturn;

    }

    function guardar_proveedores($aForm)
    {
        global $DSN_Ifx;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $oIfx = new Dbo;
        $oIfx->DSN = $DSN_Ifx;
        $oIfx->Conectar();

        $oReturn = new xajaxResponse();
        $empresa = $_SESSION['U_EMPRESA'];

        $activados = 0;

        foreach ($aForm as $key => $value) {

            // SOLO CAMPOS QUE SEAN prov_123
            if (strpos($key, "prov_") === 0) {

                $codigo = intval($value);

                // ACTUALIZAR ESTADO A ACTIVO
                $sql = "
                    UPDATE saeclpv
                    SET clpv_est_clpv = 'A'
                    WHERE clpv_cod_empr = $empresa
                    AND clpv_cod_clpv = $codigo
                ";

                if ($oIfx->Query($sql)) {
                    $activados++;
                }
            }
        }

        // Mensaje al usuario
        $oReturn->alert("Proveedores activados correctamente");

        $oReturn->script("consultar()");

        return $oReturn;
    }


    $xajax->processRequest();

?>

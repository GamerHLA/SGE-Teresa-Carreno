<?php
// Disable error reporting to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../includes/config.php';

try {
    $sql = "SELECT 
                a.alumno_id,
                a.cedula,
                a.nombre,
                a.apellido,
                a.talla_camisa,
                a.talla_pantalon,
                a.actividad_extra,
                a.id_nacionalidades,
                (SELECT GROUP_CONCAT(
                    CONCAT(o.tipo_observacion, '|', DATE_FORMAT(o.fecha_creacion, '%d/%m/%Y'), '|', TO_BASE64(o.observacion)) 
                    SEPARATOR '###') 
                 FROM observaciones o 
                 WHERE o.alumno_id = a.alumno_id AND o.estatus = 1
                 ORDER BY o.fecha_creacion DESC) as observaciones_concat
            FROM alumnos a
            WHERE a.estatus != 0
            ORDER BY a.alumno_id ASC";

    $query = $pdo->prepare($sql);
    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    $nacionalidadMap = [1 => 'E', 2 => 'P', 3 => 'V'];

    for ($i = 0; $i < count($data); $i++) {
        $id = $data[$i]['alumno_id'];
        $data[$i]['numero_registro'] = $i + 1;

        $id_nacionalidades = $data[$i]['id_nacionalidades'] ?? 3;
        $nacionalidad = $nacionalidadMap[$id_nacionalidades] ?? 'V';
        $data[$i]['cedula_completa'] = $nacionalidad . '-' . ($data[$i]['cedula'] ?? '');
        $data[$i]['nombre_completo'] = ($data[$i]['nombre'] ?? '') . ' ' . ($data[$i]['apellido'] ?? '');

        // Tallas
        $tc = $data[$i]['talla_camisa'];
        $tp = $data[$i]['talla_pantalon'];
        $tallas = [];
        if ($tc)
            $tallas[] = "Camisa: $tc";
        if ($tp)
            $tallas[] = "Pantalón: $tp";
        $data[$i]['talla_uniforme'] = !empty($tallas) ? implode(', ', $tallas) : '<span class="text-muted"><i>Sin registro</i></span>';

        // FETCH CONDITIONS (Salud Escolar) & EMERGENCY
        $sqlHealth = "SELECT 
                        se.diagnostico, 
                        se.fecha_diagnostico,
                        ef.enfermedades as enf_nombre,
                        di.discapacidad as disc_nombre,
                        se.alergias,
                        se.contacto_emergencia,
                        se.telefono_emergencia,
                        p_rel.parentesco as parentesco,
                        gs.grupo_sanguineo as blood_name
                      FROM informacion_medica se
                      LEFT JOIN enfermedad ef ON se.enfermedad_id = ef.id_enfermedad_cronica
                      LEFT JOIN discapacidad di ON se.discapacidad_id = di.id_discapacidad
                      LEFT JOIN parentesco p_rel ON se.parentesco_id = p_rel.id_parentesco
                      LEFT JOIN grupo_sanguineo gs ON se.grupo_sanguineo_id = gs.id_grupo_sanguineo
                      WHERE se.alumno_id = ?";
        $qH = $pdo->prepare($sqlHealth);
        $qH->execute([$id]);
        $conditions = $qH->fetchAll(PDO::FETCH_ASSOC);

        // FETCH VACCINES
        $sqlVax = "SELECT v.nombre FROM alumno_vacunas av INNER JOIN vacunas_infantiles v ON av.vacuna_id = v.id_vacuna_infantil WHERE av.alumno_id = ?";
        $qV = $pdo->prepare($sqlVax);
        $qV->execute([$id]);
        $vaxList = $qV->fetchAll(PDO::FETCH_COLUMN);
        $vax = implode(', ', $vaxList);

        $listaEnf = [];
        $listaDisc = [];
        $emergenciaHtml = "";
        $blood = "";

        foreach ($conditions as $c) {
            if (!empty($c['enf_nombre'])) {
                $str = "<div class='mb-1'><strong>" . $c['enf_nombre'] . "</strong>";
                if (!empty($c['alergias']))
                    $str .= " <br><span class='text-danger'>Alergia: {$c['alergias']}</span>";
                if (!empty($c['diagnostico']))
                    $str .= "<br><span>" . $c['diagnostico'] . "</span>";
                $str .= "</div>";
                $listaEnf[] = $str;
            }
            if (!empty($c['disc_nombre'])) {
                $str = "<div class='mb-1'><strong>" . $c['disc_nombre'] . "</strong>";
                if (!empty($c['diagnostico']))
                    $str .= "<br><span>" . $c['diagnostico'] . "</span>";
                $str .= "</div>";
                $listaDisc[] = $str;
            }
            if (empty($emergenciaHtml) && !empty($c['contacto_emergencia'])) {
                $parentName = !empty($c['parentesco']) ? $c['parentesco'] : 'No especificado';
                
                $emPhone = $c['telefono_emergencia'];
                if (strlen(preg_replace('/[^0-9]/', '', $emPhone)) == 11) {
                     $emPhone = substr($emPhone, 0, 4) . '-' . substr($emPhone, 4);
                }

                $emergenciaHtml = "<div class='mt-3 p-2 rounded' style='border: 2px solid #e65100; background-color: #fff3e0;'>
                    <span style='color: #e65100; font-weight: bold;'><i class='fas fa-exclamation-triangle'></i> Contacto de Emergencia:</span><br>
                    <span class='font-weight-bold' style='color: #333;'>{$c['contacto_emergencia']}</span> <span class='text-muted'>({$parentName})</span><br>
                    <i class='fas fa-phone' style='color: #e65100;'></i> <span style='color: #333;'>{$emPhone}</span>
                </div>";
            }
            if (empty($blood) && !empty($c['blood_name'])) {
                $blood = $c['blood_name'];
            }
        }

        // Column "Enfermedades / Discapacidades"
        $htmlCond = "<div class='text-left'>";
        if (!empty($listaEnf)) {
            $htmlCond .= "<div class='mb-2'><span class='text-danger font-weight-bold' style='font-size:1.1em;'>Enfermedades:</span>" . implode('', $listaEnf) . "</div>";
        } else {
            $htmlCond .= "<div class='mb-2'><span class='text-muted'>Ninguna enfermedad registrada</span></div>";
        }

        if (!empty($listaDisc)) {
            $htmlCond .= "<div class='mb-2'><span class='text-info font-weight-bold' style='font-size:1.1em;'>Discapacidades:</span>" . implode('', $listaDisc) . "</div>";
        } else {
            $htmlCond .= "<div class='mb-2'><span class='text-muted'>Ninguna discapacidad registrada</span></div>";
        }

        if (!empty($emergenciaHtml)) {
            $htmlCond .= $emergenciaHtml;
        }
        $htmlCond .= "</div>";

        $data[$i]['enfermedades'] = $htmlCond;

        // Column "Informacion Medica Adicional"
        $infoHtml = [];
        if (!empty($blood))
            $infoHtml[] = "<strong>Grupo Sanguíneo:</strong> " . $blood;
        if (!empty($vax))
            $infoHtml[] = "<strong>Vacunas:</strong> " . $vax;



        // Fetch Medical Attention
        $sqlAtt = "SELECT tipo_atencion, nombre_doctor, telefono FROM alumnos_atencion_medica WHERE alumno_id = ?";
        $qAtt = $pdo->prepare($sqlAtt);
        $qAtt->execute([$id]);
        $attentionList = $qAtt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($attentionList)) {
            $attHtml = "<strong>Atención Médica:</strong><ul class='mb-0 pl-3'>";
            foreach($attentionList as $att) {
                $phone = $att['telefono'];
                // Format: XXXX-XXXXXXX
                if (strlen(preg_replace('/[^0-9]/', '', $phone)) == 11) {
                     $phone = substr($phone, 0, 4) . '-' . substr($phone, 4);
                }
                
                $attHtml .= "<li><strong>{$att['tipo_atencion']}:</strong> Dr. {$att['nombre_doctor']} <br>Telf: {$phone}</li>";
            }
            $attHtml .= "</ul>";
            $infoHtml[] = $attHtml;
        }

        $data[$i]['info_medica_adicional'] = !empty($infoHtml) ? "<div class='text-left'>" . implode('<hr class="my-2">', $infoHtml) . "</div>" : '<span class="text-muted"><i>Sin registro</i></span>';
        $data[$i]['actividad_extra'] = $data[$i]['actividad_extra'] ?: '<span class="text-muted"><i>Sin registro</i></span>';
        $data[$i]['observaciones'] = $data[$i]['observaciones_concat'];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
die();
?>
<?php

namespace App\Support;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Support\Str;

class DefaultIncidentCategoryCatalog
{
    /**
     * @var list<array{name: string, description: string, examples: string}>
     */
    private const array Items = [
        [
            'name' => 'Actos inseguros',
            'description' => 'Acciones u omisiones realizadas por empleados, contratistas o visitantes que incumplen las normas, procedimientos o prácticas de seguridad industrial establecidas por la empresa, generando condiciones de riesgo que pueden provocar accidentes, lesiones, daños a las instalaciones, equipos o interrupciones en las operaciones.',
            'examples' => 'Incumplimiento de protocolos de seguridad, uso inadecuado del equipo de protección personal (EPP), ingreso a áreas restringidas sin autorización, desobediencia de señalizaciones de seguridad o realización de actividades contrarias a los procedimientos establecidos.',
        ],
        [
            'name' => 'Condiciones inseguras',
            'description' => 'Situación o condición física, ambiental u operativa dentro de las instalaciones de la empresa que representa un riesgo para la seguridad y salud de los empleados, contratistas o visitantes, y que puede provocar accidentes, lesiones, daños a equipos e infraestructura o interrupciones en las operaciones.',
            'examples' => 'Pisos resbalosos, cables eléctricos expuestos, maquinaria sin dispositivos de protección, iluminación deficiente, obstrucción de rutas de evacuación, almacenamiento inadecuado de materiales, herramientas o equipos defectuosos, fugas de sustancias peligrosas y señalización de seguridad inexistente o deteriorada.',
        ],
        [
            'name' => 'Sustancias químicas y materiales peligrosos',
            'description' => 'Evento o situación relacionada con el manejo, almacenamiento, transporte, uso o disposición inadecuada de sustancias químicas y materiales peligrosos dentro de las instalaciones de la empresa, que pueda representar un riesgo para la salud e integridad de los empleados, contratistas o visitantes, así como provocar daños a las instalaciones, equipos, medio ambiente o interrupciones en las operaciones.',
            'examples' => 'Derrames de sustancias químicas, fugas de gases peligrosos, exposición a materiales tóxicos, almacenamiento inadecuado de productos químicos, mezcla de sustancias incompatibles, recipientes sin identificación o etiquetado, manejo incorrecto de residuos peligrosos, falta de equipo de protección personal (EPP) y ausencia de medidas de contención o ventilación adecuadas.',
        ],
        [
            'name' => 'Incendios y explosiones',
            'description' => 'Evento o situación relacionada con la presencia de fuego, combustión no controlada o explosiones dentro de las instalaciones de la empresa, que pueda representar un riesgo para la vida, salud e integridad de los empleados, contratistas o visitantes, así como ocasionar daños a la infraestructura, maquinaria, equipos, materiales, medio ambiente o interrupciones en las operaciones y continuidad del negocio.',
            'examples' => 'Conatos de incendio, incendios en maquinaria o equipos eléctricos, explosiones por acumulación de gases o vapores inflamables, sobrecalentamiento de equipos, cortocircuitos que provoquen fuego, ignición de sustancias químicas, manejo inadecuado de materiales combustibles, trabajos de soldadura o corte sin medidas de seguridad y fallas en los sistemas de prevención y protección contra incendios.',
        ],
        [
            'name' => 'Daño a propiedad',
            'description' => 'Evento o situación que ocasione daños parciales o totales a las instalaciones, infraestructura, maquinaria, equipos, vehículos, mobiliario, materiales u otros bienes propiedad de la empresa o de terceros, como consecuencia de accidentes, actos inseguros, condiciones inseguras, negligencia, uso inadecuado de recursos o acciones intencionales, que puedan generar pérdidas económicas, riesgos para la seguridad o interrupciones en las operaciones.',
            'examples' => 'Daños a maquinaria y equipos de producción, colisiones de montacargas contra infraestructura, deterioro de instalaciones, daños a vehículos, destrucción de mobiliario, afectaciones a sistemas eléctricos, daños ocasionados por maniobras incorrectas, uso indebido de herramientas, vandalismo y afectaciones a bienes de empleados, contratistas, visitantes o terceros.',
        ],
        [
            'name' => 'Altercado entre personal',
            'description' => 'Evento o situación de conflicto, confrontación o desacuerdo entre empleados, contratistas o personal que desempeñe actividades dentro de las instalaciones de la empresa, que involucre discusiones acaloradas, agresiones verbales o físicas, amenazas, intimidación o conductas inapropiadas que puedan comprometer la seguridad, integridad física y bienestar de las personas, afectar el ambiente laboral o generar interrupciones en las operaciones.',
            'examples' => 'Discusiones acaloradas entre empleados, confrontaciones verbales, insultos, amenazas, empujones, agresiones físicas, intimidación, conflictos entre compañeros de trabajo, desacuerdos que escalen a comportamientos agresivos y situaciones que requieran la intervención del personal de seguridad, supervisores o Recursos Humanos.',
        ],
        [
            'name' => 'Intrusión perimetral',
            'description' => 'Evento o situación relacionada con el ingreso, intento de ingreso o presencia no autorizada de personas dentro del perímetro de seguridad de las instalaciones de la empresa, mediante la vulneración, evasión o manipulación de barreras físicas, cercos, bardas, puertas, portones u otros sistemas de protección perimetral, que pueda comprometer la seguridad del personal, los activos, la infraestructura o la continuidad de las operaciones.',
            'examples' => 'Escalamiento de bardas o cercos perimetrales, ingreso por accesos no autorizados, corte o daño de mallas de seguridad, manipulación de portones, presencia de personas ajenas dentro de áreas restringidas, intentos de ingreso clandestino, evasión de controles de acceso y detección de intrusos mediante cámaras de videovigilancia, sensores o sistemas de alarma perimetral.',
        ],
        [
            'name' => 'Puertas y candados abiertos',
            'description' => 'Evento o situación relacionada con la detección de puertas, portones, rejas, candados u otros dispositivos de cierre y aseguramiento que se encuentren abiertos, sin asegurar o en condiciones inadecuadas dentro de las instalaciones de la empresa, incumpliendo los procedimientos de seguridad establecidos y generando vulnerabilidades que puedan facilitar accesos no autorizados, robos, intrusiones, daños a la propiedad o comprometer la seguridad del personal, los activos y la continuidad de las operaciones.',
            'examples' => 'Puertas de acceso abiertas sin supervisión, candados sin colocar o asegurar correctamente, portones perimetrales abiertos fuera de horarios autorizados, puertas de almacenes o áreas restringidas sin cerrar, puertas de oficinas administrativas, dispositivos de cierre dañados o manipulados, accesos que permanecen abiertos después de realizar actividades operativas y omisión de procedimientos de cierre y aseguramiento de instalaciones.',
        ],
        [
            'name' => 'Scrap fuera de lugar',
            'description' => 'Evento o situación relacionada con la detección de materiales de desecho, desperdicios de producción, residuos industriales o scrap que se encuentren almacenados, depositados o abandonados fuera de las áreas designadas y autorizadas por la empresa, incumpliendo los procedimientos establecidos para su manejo, almacenamiento, control y disposición. Esta condición puede generar riesgos para la seguridad del personal, pérdidas económicas, afectaciones al medio ambiente, discrepancias en los inventarios o vulnerabilidades en el control de materiales y activos de la organización.',
            'examples' => 'Scrap abandonado en pasillos o áreas de producción, materiales de desecho fuera de contenedores autorizados, residuos industriales almacenados en zonas no designadas, acumulación de desperdicios que obstruyan rutas de circulación o evacuación, materiales recuperables sin identificación, traslado de scrap sin autorización, residuos mezclados incorrectamente y detección de materiales de desecho en áreas restringidas o sin supervisión.',
        ],
        [
            'name' => 'Vehículo no autorizado',
            'description' => 'Evento o situación relacionada con el ingreso, intento de ingreso, circulación, estacionamiento o permanencia de vehículos que no cuenten con la autorización correspondiente dentro de las instalaciones, estacionamientos, patios de maniobras o áreas restringidas de la empresa, incumpliendo los procedimientos y controles de seguridad establecidos. Esta situación puede representar un riesgo para la integridad del personal, la protección de los activos, la seguridad de las instalaciones y la continuidad de las operaciones.',
            'examples' => 'Vehículos que ingresan sin autorización, unidades no registradas en el sistema de control de accesos, vehículos estacionados en áreas restringidas, unidades de proveedores o contratistas sin permiso de ingreso, vehículos que evaden los puntos de inspección, circulación por rutas no autorizadas, permanencia de unidades fuera de los horarios establecidos y vehículos sospechosos detectados dentro de las instalaciones.',
        ],
    ];

    /**
     * @return list<array{name: string, code: string, description: string}>
     */
    public static function definitions(): array
    {
        return array_map(fn (array $item): array => [
            'name' => $item['name'],
            'code' => Str::upper(Str::slug($item['name'], '_')),
            'description' => $item['description'].' Ejemplos: '.$item['examples'],
        ], self::Items);
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    public static function summaries(): array
    {
        return array_map(fn (array $definition): array => [
            'name' => $definition['name'],
            'description' => $definition['description'],
        ], self::definitions());
    }

    public static function importFor(Area $area): int
    {
        $created = 0;

        foreach (self::definitions() as $definition) {
            $category = IncidentCategory::query()->firstOrCreate(
                [
                    'area_id' => $area->id,
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ],
            );

            if ($category->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}

<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseTypeOfInjury;
use App\Models\NSFIRM\InjuryAnimalRelatedDeath;
use App\Models\NSFIRM\InjuryAsphyxiaDeath;
use App\Models\NSFIRM\InjuryBlastInjuries;
use App\Models\NSFIRM\InjuryBluntForceTrauma;
use App\Models\NSFIRM\InjuryCompressionPressureOfNeck;
use App\Models\NSFIRM\InjuryCoPoisoning;
use App\Models\NSFIRM\InjuryDrowning;
use App\Models\NSFIRM\InjuryElectrocution;
use App\Models\NSFIRM\InjuryFallFromHeight;
use App\Models\NSFIRM\InjuryFirearmInjuries;
use App\Models\NSFIRM\InjuryLightning;
use App\Models\NSFIRM\InjuryNotElsewhereClassified;
use App\Models\NSFIRM\InjuryProcedureRelatedDeath;
use App\Models\NSFIRM\InjurySharpTrauma;
use App\Models\NSFIRM\InjurySubstanceRelatedDeath;
use App\Models\NSFIRM\InjuryThermalInjury;
use App\Models\NSFIRM\InjuryTransportationRelatedDeath;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetInjuryDetailsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_injury_details';
    }

    public function description(): string
    {
        return 'Get the detailed forensic findings for a specific type of injury in the NSFIRM system — the '
            . 'circumstances, mechanism, and injury-specific fields behind a case\'s recorded injury type. '
            . 'Injury types are coded T1 to T17 (T1 transportation, T2 compression/pressure to neck, T3 asphyxia, '
            . 'T4 fall from height, T5 drowning, T6 thermal, T7 blunt force trauma, T8 sharp trauma, T9 firearm, '
            . 'T10 electrocution, T11 lightning, T12 animal related, T13 blast, T14 substance related, '
            . 'T15 procedure related, T16 not elsewhere classified, T17 CO poisoning). '
            . 'Each type stores different columns, so pass "injury_code" to choose one. Every record is 1:1 with a case. '
            . 'Use get_case_type_of_injuries first if you need to know which injury types a case has. '
            . 'Set "include_children" to also return the multi-row body-region / sub-injury detail for the types that have it.';
    }

    /**
     * Injury type code => the detail table behind it, plus the multi-row child relations
     * defined on that model. Child relation names are NOT derivable from the code
     * (T1 has seven bespoke children, T4 splits low/high fall), so the map is explicit.
     *
     * @return array<string, array{model: class-string, name: string, children: array<int, string>}>
     */
    protected function injuryTypes(): array
    {
        return [
            'T1' => ['model' => InjuryTransportationRelatedDeath::class, 'name' => 'Transportation Related Death', 'children' => ['landInjuries', 'airInjuries', 'waterInjuries', 'headInjuries', 'neckInjuries', 'chestInjuries', 'abdomenInjuries']],
            'T2' => ['model' => InjuryCompressionPressureOfNeck::class, 'name' => 'Compression/Pressure to Neck', 'children' => []],
            'T3' => ['model' => InjuryAsphyxiaDeath::class, 'name' => 'Asphyxia Death', 'children' => []],
            'T4' => ['model' => InjuryFallFromHeight::class, 'name' => 'Fall from Height', 'children' => ['lowFallInjuries', 'highFallInjuries']],
            'T5' => ['model' => InjuryDrowning::class, 'name' => 'Drowning', 'children' => []],
            'T6' => ['model' => InjuryThermalInjury::class, 'name' => 'Thermal Injury', 'children' => []],
            'T7' => ['model' => InjuryBluntForceTrauma::class, 'name' => 'Blunt Force Trauma', 'children' => ['regionsOfInjury']],
            'T8' => ['model' => InjurySharpTrauma::class, 'name' => 'Sharp Force Trauma', 'children' => ['regionsOfInjury']],
            'T9' => ['model' => InjuryFirearmInjuries::class, 'name' => 'Firearm Injuries', 'children' => ['regionsOfInjury']],
            'T10' => ['model' => InjuryElectrocution::class, 'name' => 'Electrocution', 'children' => ['regionsOfInjury']],
            'T11' => ['model' => InjuryLightning::class, 'name' => 'Lightning Strike', 'children' => ['regionsOfInjury']],
            'T12' => ['model' => InjuryAnimalRelatedDeath::class, 'name' => 'Animal Related Death', 'children' => []],
            'T13' => ['model' => InjuryBlastInjuries::class, 'name' => 'Blast Injuries', 'children' => ['regionsOfInjury']],
            'T14' => ['model' => InjurySubstanceRelatedDeath::class, 'name' => 'Substance Related Death', 'children' => []],
            'T15' => ['model' => InjuryProcedureRelatedDeath::class, 'name' => 'Procedure Related Death', 'children' => []],
            'T16' => ['model' => InjuryNotElsewhereClassified::class, 'name' => 'Not Elsewhere Classified', 'children' => []],
            'T17' => ['model' => InjuryCoPoisoning::class, 'name' => 'CO Poisoning', 'children' => []],
        ];
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'injury_code' => [
                    'type' => 'string',
                    'enum' => array_keys($this->injuryTypes()),
                    'description' => 'Which injury type\'s detail to return (T1 to T17). Required.',
                ],
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id. Each case has at most one detail row per injury type.'],
                'completed_only' => ['type' => 'boolean', 'description' => 'Only records whose detail form is marked complete.'],
                'include_children' => [
                    'type' => 'boolean',
                    'description' => 'Also return the multi-row sub-detail (body regions injured, or the '
                    . 'land/air/water breakdown for T1) under each row\'s "relations" key. '
                    . 'Only T1, T4, T7, T8, T9, T10, T11 and T13 have any.',
                ],
                'include_case' => ['type' => 'boolean', 'description' => 'Also return the parent case registration with its status and source hospital.'],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 200, max 1000). Detail rows are wide, so keep this small.'],
            ],
            'required' => ['injury_code'],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        // TODO: tighten this to your needs. Defaults to authenticated users only.
        return $request?->user() !== null;
    }

    public function handle(array $arguments): mixed
    {
        $types = $this->injuryTypes();
        $code = strtoupper((string) ($arguments['injury_code'] ?? ''));

        if (!isset($types[$code])) {
            return [
                'error' => 'Unknown injury_code. Choose one of: ' . implode(', ', array_keys($types)) . '.',
            ];
        }

        $config = $types[$code];
        $model = $config['model'];
        $query = $model::query();

        $withChildren = !empty($arguments['include_children']) && $config['children'] !== [];
        if ($withChildren) {
            // Each child row resolves its own code against its ref_t* lookup.
            $query->with(array_map(fn(string $rel) => $rel . '.ref:id,code,name', $config['children']));
        }
        if (!empty($arguments['include_case'])) {
            $query->with(['caseRegistration.status:id,status_name', 'caseRegistration.sourceHospital:id,name,facilityCode,state_code']);
        }

        if (isset($arguments['case_id'])) {
            $query->where('case_id', $arguments['case_id']);
        }
        if (!empty($arguments['completed_only'])) {
            $query->where('completion_flag', 1);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 200), 1), 1000);

        $rows = $query->limit($limit)->get()->map(function ($row) use ($config, $code, $withChildren, $arguments) {
            // Detail tables are wide and mostly nullable; drop the empties so the
            // model sees the findings that were actually recorded, not 40 nulls.
            $data = array_filter(
                $row->attributesToArray(),
                fn($value) => $value !== null && $value !== ''
            );
            $data['injury_code'] = $code;
            $data['injury_name'] = $config['name'];

            if ($withChildren) {
                foreach ($config['children'] as $relation) {
                    $data['relations'][$relation] = $row->{$relation}
                        ->map(fn($child) => ['code' => $child->ref?->code, 'name' => $child->ref?->name])
                        ->all();
                }
            }
            if (!empty($arguments['include_case'])) {
                $data['relations']['case'] = $row->caseRegistration?->toArray();
            }

            return $data;
        });

        return [
            'injury_code' => $code,
            'injury_name' => $config['name'],
            'count' => $rows->count(),
            'total_cases_with_this_injury' => CaseTypeOfInjury::where('injury_code', $code)->where('active', 1)->distinct()->count('case_id'),
            'injury_details' => $rows->all(),
        ];
    }
}

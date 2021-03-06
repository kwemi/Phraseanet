<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Media\SubdefSubstituer;
use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use Symfony\Component\HttpFoundation\Request;

class EditController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use SubDefinitionSubstituerAware;

    public function submitAction(Request $request)
    {
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            RecordsRequest::FLATTEN_YES_PRESERVE_STORIES,
            ['canmodifrecord']
        );

        $thesaurus = false;
        $status = $ids = $elements = $suggValues = $fields = $JSFields = [];
        $databox = null;
        $databoxes = $records->databoxes();

        $multipleDataboxes = count($databoxes) > 1;

        if (1 === count($databoxes)) {
            /** @var \databox $databox */
            $databox = current($databoxes);

            // generate javascript fields
            foreach ($databox->get_meta_structure() as $meta) {
                /** @var \databox_field $meta */
                $fields[] = $meta;

                $separator = $meta->get_separator();

                /** @Ignore */
                $JSFields[$meta->get_id()] = [
                    'meta_struct_id'       => $meta->get_id(),
                    'name'                 => $meta->get_name(),
                    '_status'              => 0,
                    '_value'               => '',
                    '_sgval'               => [],
                    'required'             => $meta->is_required(),
                    /** @Ignore */
                    'label'                => $meta->get_label($this->app['locale']),
                    'readonly'             => $meta->is_readonly(),
                    'type'                 => $meta->get_type(),
                    'format'               => '',
                    'explain'              => '',
                    'tbranch'              => $meta->get_tbranch(),
                    'maxLength'            => $meta->get_tag()
                        ->getMaxLength(),
                    'minLength'            => $meta->get_tag()
                        ->getMinLength(),
                    'multi'                => $meta->is_multi(),
                    'separator'            => $separator,
                    'vocabularyControl'    => $meta->getVocabularyControl() ? $meta->getVocabularyControl()
                        ->getType() : null,
                    'vocabularyRestricted' => $meta->getVocabularyControl() ? $meta->isVocabularyRestricted()
                        : false,
                ];

                if (trim($meta->get_tbranch()) !== '') {
                    $thesaurus = true;
                }
            }

            // generate javascript sugg values
            foreach ($records->collections() as $collection) {
                $suggValues['b' . $collection->get_base_id()] = [];

                if ($sxe = simplexml_load_string($collection->get_prefs())) {
                    $z = $sxe->xpath('/baseprefs/sugestedValues');

                    if (!$z || !is_array($z)) {
                        continue;
                    }

                    foreach ($z[0] as $ki => $vi) { // les champs
                        $field = $databox->get_meta_structure()
                            ->get_element_by_name($ki);
                        if (!$field || !$vi) {
                            continue;
                        }

                        $suggValues['b' . $collection->get_base_id()][$field->get_id()] = [];

                        foreach ($vi->value as $oneValue) {
                            $suggValues['b' . $collection->get_base_id()][$field->get_id()][] = (string)$oneValue;
                        }
                    }
                }
                unset($collection);
            }

            // generate javascript status
            if ($this->getAclForUser()->has_right('changestatus')) {
                $statusStructure = $databox->getStatusStructure();
                foreach ($statusStructure as $statbit) {
                    $bit = $statbit['bit'];

                    $status[$bit] = [];
                    $status[$bit]['label0'] = $statbit['labels_off_i18n'][$this->app['locale']];
                    $status[$bit]['label1'] = $statbit['labels_on_i18n'][$this->app['locale']];
                    $status[$bit]['img_off'] = $statbit['img_off'];
                    $status[$bit]['img_on'] = $statbit['img_on'];
                    $status[$bit]['_value'] = 0;
                }
            }

            // generate javascript elements
            $databox_fields = [];
            foreach ($databox->get_meta_structure() as $field) {
                $databox_fields[$field->get_id()] = [
                    'dirty'          => false,
                    'meta_struct_id' => $field->get_id(),
                    'values'         => []
                ];
            }

            /** @var \record_adapter $record */
            foreach ($records as $record) {
                $indice = $record->get_number();
                $elements[$indice] = [
                    'bid'         => $record->get_base_id(),
                    'rid'         => $record->get_record_id(),
                    'sselcont_id' => null,
                    '_selected'   => false,
                    'fields'      => $databox_fields,
                ];

                $elements[$indice]['statbits'] = [];
                if ($this->getAclForUser()->has_right_on_base($record->get_base_id(), 'chgstatus')) {
                    foreach ($status as $n => $s) {
                        $tmp_val = substr(strrev($record->get_status()), $n, 1);
                        $elements[$indice]['statbits'][$n]['value'] = ($tmp_val == '1') ? '1' : '0';
                        $elements[$indice]['statbits'][$n]['dirty'] = false;
                    }
                }

                $elements[$indice]['originalname'] = $record->get_original_name();

                foreach ($record->get_caption()->get_fields(null, true) as $field) {
                    $meta_struct_id = $field->get_meta_struct_id();
                    if (!isset($JSFields[$meta_struct_id])) {
                        continue;
                    }

                    $values = [];
                    foreach ($field->get_values() as $value) {
                        $type = $id = null;

                        if ($value->getVocabularyType()) {
                            $type = $value->getVocabularyType()->getType();
                            $id = $value->getVocabularyId();
                        }

                        $values[$value->getId()] = [
                            'meta_id'        => $value->getId(),
                            'value'          => $value->getValue(),
                            'vocabularyId'   => $id,
                            'vocabularyType' => $type,
                        ];
                    }

                    $elements[$indice]['fields'][$meta_struct_id] = [
                        'dirty'          => false,
                        'meta_struct_id' => $meta_struct_id,
                        'values'         => $values,
                    ];
                }

                $elements[$indice]['subdefs'] = [];

                $thumbnail = $record->get_thumbnail();

                $elements[$indice]['subdefs']['thumbnail'] = [
                    'url' => (string)$thumbnail->get_url(),
                    'w'   => $thumbnail->get_width(),
                    'h'   => $thumbnail->get_height(),
                ];

                $elements[$indice]['preview'] = $this->render(
                    'common/preview.html.twig',
                    ['record' => $record]
                );

                $elements[$indice]['type'] = $record->get_type();
            }
        }

        $params = [
            'multipleDataboxes' => $multipleDataboxes,
            'recordsRequest'    => $records,
            'databox'           => $databox,
            'JSonStatus'        => json_encode($status),
            'JSonRecords'       => json_encode($elements),
            'JSonFields'        => json_encode($JSFields),
            'JSonIds'           => json_encode(array_keys($elements)),
            'status'            => $status,
            'fields'            => $fields,
            'JSonSuggValues'    => json_encode($suggValues),
            'thesaurus'         => $thesaurus,
        ];

        return $this->render('prod/actions/edit_default.html.twig', $params);
    }

    public function searchVocabularyAction(Request $request, $vocabulary) {
        $datas = ['success' => false, 'message' => '', 'results' => []];

        $sbas_id = (int) $request->query->get('sbas_id');

        try {
            if ($sbas_id === 0) {
                throw new \Exception('Invalid sbas_id');
            }

            $VC = VocabularyController::get($this->app, $vocabulary);
            $databox = $this->findDataboxById($sbas_id);
        } catch (\Exception $e) {
            $datas['message'] = $this->app->trans('Vocabulary not found');

            return $this->app->json($datas);
        }

        $query = $request->query->get('query');

        $results = $VC->find($query, $this->getAuthenticatedUser(), $databox);

        $list = [];

        foreach ($results as $Term) {
            /* @var \Alchemy\Phrasea\Vocabulary\Term $Term */
            $list[] = [
                'id'      => $Term->getId(),
                'context' => $Term->getContext(),
                'value'   => $Term->getValue(),
            ];
        }

        $datas['success'] = true;
        $datas['results'] = $list;

        return $this->app->json($datas);
    }

    public function applyAction(Request $request) {

        $records = RecordsRequest::fromRequest($this->app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, ['canmodifrecord']);

        $databoxes = $records->databoxes();
        if (count($databoxes) !== 1) {
            throw new \Exception('Unable to edit on multiple databoxes');
        }
        /** @var \databox $databox */
        $databox = reset($databoxes);

        if ($request->request->get('act_option') == 'SAVEGRP'
            && $request->request->get('newrepresent')
            && $records->isSingleStory()
        ) {
            try {
                $reg_record = $records->singleStory();

                $newsubdef_reg = new \record_adapter($this->app, $reg_record->get_sbas_id(), $request->request->get('newrepresent'));

                foreach ($newsubdef_reg->get_subdefs() as $name => $value) {
                    if (!in_array($name, ['thumbnail', 'preview'])) {
                        continue;
                    }
                    if ($value->get_type() !== \media_subdef::TYPE_IMAGE) {
                        continue;
                    }

                    $media = $this->app->getMediaFromUri($value->get_pathfile());
                    $this->getSubDefinitionSubstituer()->substitute($reg_record, $name, $media);
                    $this->getDispatcher()->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($reg_record));
                    $this->getDataboxLogger($reg_record->get_databox())->log(
                        $reg_record,
                        \Session_Logger::EVENT_SUBSTITUTE,
                        $name == 'document' ? 'HD' : $name,
                        ''
                    );
                }
            } catch (\Exception $e) {

            }
        }

        if (!is_array($request->request->get('mds'))) {
            return $this->app->json(['message' => '', 'error'   => false]);
        }

        $elements = $records->toArray();

        foreach ($request->request->get('mds') as $rec) {
            try {
                $record = $databox->get_record($rec['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $key = $record->get_serialize_key();

            if (!array_key_exists($key, $elements)) {
                continue;
            }

            $statbits = $rec['status'];
            $editDirty = $rec['edit'];

            if ($editDirty == '0') {
                $editDirty = false;
            } else {
                $editDirty = true;
            }

            if (isset($rec['metadatas']) && is_array($rec['metadatas'])) {
                $record->set_metadatas($rec['metadatas']);
                $this->getDispatcher()->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));
            }

            $newstat = $record->get_status();
            $statbits = ltrim($statbits, 'x');
            if (!in_array($statbits, ['', 'null'])) {
                $mask_and = ltrim(str_replace(['x', '0', '1', 'z'], ['1', 'z', '0', '1'], $statbits), '0');
                if ($mask_and != '') {
                    $newstat = \databox_status::operation_and_not($newstat, $mask_and);
                }

                $mask_or = ltrim(str_replace('x', '0', $statbits), '0');

                if ($mask_or != '') {
                    $newstat = \databox_status::operation_or($newstat, $mask_or);
                }

                $record->set_binary_status($newstat);
            }

            $record
                ->write_metas()
                ->get_collection()
                ->reset_stamp($record->get_record_id());

            if ($statbits != '') {
                $this->getDataboxLogger($databox)
                    ->log($record, \Session_Logger::EVENT_STATUS, '', '');
            }
            if ($editDirty) {
                $this->getDataboxLogger($databox)
                    ->log($record, \Session_Logger::EVENT_EDIT, '', '');
            }
        }

        return $this->app->json(['success' => true]);
    }
}

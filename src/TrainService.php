<?php
//namespace App\Services;
namespace TrainVox\TrainService;

use App\Models\TrainModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrainService
{
    public $client;
    public $version;

    public $workspaceId;
    public $projectId;
    public $apiStatus;

    public const API_GET_LIST_MODEL = '/%s/archs/%s/%s';
    public const API_TRAIN_MODEL = '/%s/fit';
    public const API_TRAIN_DETAIL = '/%s/track/%s/%s/%s';

    # model train name
    public const MODEL_TRAIN_NAME = 'obj_master_vizo_%s_train';

    public function __construct()
    {
        $this->apiStatus = config('services.obm.status');
        $this->version = config('services.obm.api_version');
        $this->client = Http::baseUrl(config('services.obm.base_url'))
            //->asForm()
            ->contentType('application/json')
            ->accept('*/*');

        $this->workspaceId = null;
        $this->projectId = null;
    }

    /**
     * API get list of model
     * Method: GET
     * URL: /{version}/archs/{workspace_id}/{project_id}
     * Payload:
     *   {
     *       "code": 200,
     *       "status": "success",
     *       "data": [
     *           {
     *               "model": "YOLOv5n",
     *               "size": 640,
     *               "map_val": {
     *                   "val_50": 28,
     *                   "val_50_59": 45.7
     *               }
     *               "speed": {
     *                   "cpu_b1": 45,
     *                   "v100_b1": 6.3,
     *                   "v100_b32": 1.9
     *               },
     *               "params_m": "1.9",
     *               "flops_b": 4.5
     *           },
     *           ...
     *       ]
     *   }
     *
     * @return array|null
     */
    public function getListModel()
    {
        try {
            if (!$this->apiStatus) {
                Log::info("[OBM] API OFF");
                return null;
            }

            Log::info("[OBM] START GET LIST MODEL");

            $modelList = null;
            $url = sprintf(self::API_GET_LIST_MODEL, $this->version, $this->workspaceId, $this->projectId);
            Log::info("[OBM] GET LIST MODEL, url: {$url}");

            $response = $this->client->get($url);
            Log::info("[OBM] GET LIST MODEL, response: ", (array)$response);

            // check api status
            if ($response->status() == 200) {
                $resBody = json_decode($response->body(), JSON_OBJECT_AS_ARRAY);
                if ($this->isValidResponse($resBody)) {
                    $modelList = isset($resBody['data']) ? $resBody['data'] : null;
                }
            }
            Log::info("[OBM] END GET LIST MODEL", (array)$modelList);

            return $modelList;
        } catch (\Exception $e) {
            Log::error("[OBM] END GET LIST MODEL = Error code: {$e->getCode()}, message: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * API train model
     * Method: POST
     * URL: /{version}/fit
     * Params:
     *   {
     *       "workspace": {
     *           "id": "workspace-slug",
     *           "project_id": "project-slug",
     *           "model_name": "obj_master_vizo_1train",
     *           "dataset": {
     *               "version_code": 234,
     *               "version_name": "ver_234_345"
     *           }
     *       },
     *       "architectures": {
     *           "name": "YOLOV5n"
     *       }
     *   }
     *
     * @param $wslug
     * @param $project
     * @param $version
     * @param $modelId
     * @param $dataset
     * @param null $opts
     * @return mixed|null
     */
    public function train($wslug, $project, $version, $modelId, $dataset, $opts = null)
    {
        try {
            $projectId = $project->id;
            Log::info("[OBM] START TRAIN MODEL projectId: {$projectId}, version: {$version}, modelId: {$modelId}");

            $url = sprintf(self::API_TRAIN_MODEL, $this->version);
            Log::info("[OBM] TRAIN MODEL, url: {$url}");

            $epochs = isset($opts['epochs']) ? (int)$opts['epochs'] : TrainModel::EPOCHS_DEFAULT;
            $epochs = $epochs <= TrainModel::EPOCHS_DEFAULT ? $epochs : TrainModel::EPOCHS_DEFAULT;
            $data = [
                "workspace" => [
                    "id" => $wslug,
                    "project_id" => $project->slug,
                    "model_name" => sprintf(self::MODEL_TRAIN_NAME, $dataset->version_no),
                    "dataset" => [
                        "version_code" => $dataset->version_no,
                        "version_name" => $dataset->version_name
                    ]
                ],
                "architectures" => [
                    "name" => $modelId
                ],
                "adv_model_conf" => [
                    "pre_trained" => isset($opts['pre_trained']) && $opts['pre_trained'],
                    "epochs" => $epochs
                ]
            ];
            Log::info("[OBM] TRAIN MODEL, request data: ", (array)$data);

            $response = $this->client->post($url, $data);

            Log::info("[OBM] TRAIN MODEL, response status: ".($response->status()));

            // check api status
            $payload = [];
            if ($response->status() == 200) {
                $payload = json_decode($response->body(), JSON_OBJECT_AS_ARRAY);
            }
            Log::info("[OBM] END TRAIN MODEL, payload: ", $payload);

            return $payload;
        } catch (\Exception $e) {
            Log::error("[OBM] END TRAIN MODEL = Error code: {$e->getCode()}, message: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * API get train detail list
     * Method: GET
     * URL: /{version}/track/{workspace_id}/{project_id}/{version_name}
     * Params:
     *    {
     *       "code": 200,
     *       "status": "success",
     *       "data": [
     *           {
     *               "step": 0,
     *               "train": {
     *                   "box_loss": 0.13058499991893768,
     *                   "obj_loss": 0.031466465443372726,
     *                   "cls_loss": 0.0
     *               },
     *               "metrics": {
     *                   "precision": 0.0,
     *                   "recall": 0.0,
     *                   "map_0_5": 0.0,
     *                   "map_0_5_0_95": 0.0
     *               },
     *               "val": {
     *                   "box_loss": 0.09064294397830963,
     *                   "obj_loss": 0.028396805748343468,
     *                   "cls_loss": 0.0
     *               },
     *               "x": {
     *                   "lr0": 0.1,
     *                   "lr1": 0.0,
     *                   "lr2": 0.0
     *               }
     *           },
     *          ...
     *       ]
     *    }
     *
     * @param $versionName
     * @return mixed|null
     */
    public function getTrainDetails($versionName): mixed
    {
        try {
            Log::info("[OBM] START GET TRAIN DETAIL");

            $trainDetails = null;
            $url = sprintf(self::API_TRAIN_DETAIL, $this->version, $this->workspaceId, $this->projectId, $versionName);
            Log::info("[OBM] GET TRAIN DETAIL, url: {$url}");

            $response = $this->client->get($url);
            Log::info("[OBM] GET TRAIN DETAIL, response: ", (array)$response);

            // check api status
            if ($response->status() == 200) {
                $resBody = json_decode($response->body(), JSON_OBJECT_AS_ARRAY);
                if ($this->isValidResponse($resBody)) {
                    $trainDetails = isset($resBody['data']) ? $resBody['data'] : null;
                }
            }
            Log::info("[OBM] END GET TRAIN DETAIL.");

            return $trainDetails;
        } catch (\Exception $e) {
            Log::error("[OBM] END GET TRAIN DETAIL = Error code: {$e->getCode()}, message: {$e->getMessage()}");
            return null;
        }
    }

    private function isValidResponse($response): bool
    {
        if ((int)$response['code'] == 200 && $response['status'] == 'success') {
            return true;
        }
        return false;
    }

    public function setWorkspaceId($workspaceId): void
    {
        $this->workspaceId = $workspaceId;
    }

    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    public function setProjectId($projectId): void
    {
        $this->projectId = $projectId;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }
}

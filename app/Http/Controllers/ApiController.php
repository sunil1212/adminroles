<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Fractal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lang;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\DataArraySerializer;
use Spatie\Fractalistic\ArraySerializer;

class ApiController extends Controller
{
    /**
     * @var int Status Code.
     */
    const TRANSFORMERS_NAMESPACE_PATH = "App\\Transformers\\";
    protected $statusCode             = 200;
    protected $message                = "";
    protected $serializer             = "League\Fractal\Serializer\DataArraySerializer";

    public function respondValidationFailed($message = 'Validation failed!')
    {
        return $this->setStatusCode(429)->respondWithError($message);
    }

    /**
     * Function to return an unauthorized response.
     *
     * @param string $message
     * @return mixed
     */
    public function respondUnauthorizedError($message = 'Unauthorized!')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Function to return forbidden error response.
     *
     * @param string $message
     * @return mixed
     */
    public function respondForbiddenError($message = 'Forbidden!')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Function to return an internal error response.
     *
     * @param string $message
     * @return mixed
     */
    public function respondInternalError($message = 'Internal Error!')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Function to return a service unavailable response.
     *
     * @param string $message
     * @return mixed
     */
    public function respondServiceUnavailable($message = "Service Unavailable!")
    {
        return $this->setStatusCode(503)->respondWithError($message);
    }

    public function success($message)
    {
        return $this->respond([
            'success' => [
                'message' => $message,
                'code'    => $this->getStatusCode(),
            ],
        ]);
    }

    /**
     * Function to return a resource created response.
     *
     * @param $data
     * @return mixed
     */
    public function respondResourceCreated(array $data)
    {
        return $this->setStatusCode(201)->respond($data);
    }

    public function respondWithStatus(array $data = [])
    {
        if (200 == $this->getStatusCode()) {
            $data["code"]    = $this->getStatusCode();
            $data["message"] = $this->getMessage();

            return $this->respond($data);

        }
        $this->setStatusCode(201);
        $data["code"]    = $this->getStatusCode();
        $data["message"] = $this->getMessage();

        return $this->respond($data);
    }

    /**
     * Function to return an error response.
     *
     * @param $message
     * @return mixed
     */
    public function respondWithError($message)
    {
        return $this->respond([
            'error' => [
                'message' => $message,
                'code'    => $this->getStatusCode(),
            ],
        ]);
    }

    /**
     * @param array $headers Headers to b used in response.
     * @return mixed Return the response.
     */
    public function respond($data = [], $headers = [])
    {
        return response()->json($data, $this->getStatusCode(), $headers);
    }

    /**
     * Getter method to return stored status code.
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Setter method to set status code.
     * It is returning current object
     * for chaining purposes.
     *
     * @param mixed $statusCode
     * @return current object.
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Function to return a resource created response.
     *
     * @param $data
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Function to return an error response.
     *
     * @param $message
     * @return mixed
     */

    public function respondWithPagination(LengthAwarePaginator $paginator, $data)
    {
        $data = collect($data);

        $data = $data->merge([
            'paginator' => [
                'total_count'  => $paginator->total(),
                'total_pages'  => ceil($paginator->total() / $paginator->perPage()),
                'current_page' => $paginator->currentPage(),
                'limit'        => $paginator->perPage(),
            ],
        ]);

        return $this->respond($data);
    }

    public function fractalCollection($modelData, $transformerName)
    {

        try {
            if ($modelData->isEmpty()) {
                return $this->respondNotFound(lang('messages.no-record'));
            }
            return $this->setStatusCode(200)
                ->setMessage("Data fetched successfully")
                ->respond([
                    "data"    => $this->collection($modelData, $transformerName),
                    "code"    => $this->getStatusCode(),
                    "message" => $this->getMessage(),
                ]);

        } catch (\Exception $e) {
            return $this->setStatusCode(500)
                ->respondWithError(lang('messages.default_error'));
        }

    }

    /**
     * Function to return a Not Found response.
     *
     * @param string $message
     * @return mixed
     */
    public function respondNotFound($message = 'Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    public function collection($modelData, $transformerName)
    {
        $transformer = self::TRANSFORMERS_NAMESPACE_PATH . $transformerName;

        return $data = fractal()
            ->collection($modelData, new $transformer())
            ->serializeWith(new ArraySerializer())
            ->toArray();
    }

    public function paginationCollection($modelData, $transformerName)
    {
        try {
            if ($modelData->isEmpty()) {
                return $this->respondNotFound(lang('messages.defaults.default-success'));
            }

            return $this->setStatusCode(200)
                ->setMessage("Data fetched successfully")
                ->respond([
                    "data"    => $this->pagination($modelData, $transformerName),
                    "code"    => $this->getStatusCode(),
                    "message" => $this->getMessage(),
                ]);

        } catch (\Exception $e) {

            return $this->setStatusCode(500)
                ->respondWithError(lang('messages.defaults.default_error'));
        }
    }

    public function pagination($modelData, $transformerName)
    {
        if ($modelData->isEmpty()) {
            return $this->respondNotFound(lang('messages.defaults.no-records'));
        }
        $transformer = self::TRANSFORMERS_NAMESPACE_PATH . $transformerName;

        return fractal()
            ->collection($modelData->getCollection(), new $transformer())
            ->serializeWith(new DataArraySerializer())
            ->paginateWith(new IlluminatePaginatorAdapter($modelData))
            ->toArray();

    }

//  getAll()

    public function getAll($modelData, $transformerName)
    {

        if (!$modelData) {
            return $this->respondNotFound(lang('messages.defaults.no-records'));
        }
        $transformer = self::TRANSFORMERS_NAMESPACE_PATH . $transformerName;
        $f           = fractal()
            ->collection($modelData, new $transformer())
            ->serializeWith(new ArraySerializer())
            ->toArray();

        return $this->setStatusCode(200)
            ->setMessage("Data fetched successfully")
            ->respond([
                "data"    => $f,
                "code"    => $this->getStatusCode(),
                "message" => $this->getMessage(),
            ]);

    }

    public function respondCreated($data, $transformer, $message)
    {

        $transformer = self::TRANSFORMERS_NAMESPACE_PATH . $transformer;
        $dataSave    = fractal()
            ->item($data, new $transformer(), '')
            ->serializeWith(new DataArraySerializer())
            ->toArray();

        return $this->setStatusCode(201)
            ->setMessage($message)
            ->respondWithStatus($dataSave);

    }

    public function simplePagination($modelData, $transformer)
    {
        $transformer = self::TRANSFORMERS_NAMESPACE_PATH . $transformer;
        $serializer  = $this->getSerializer();

        return fractal()
            ->collection($modelData->getCollection(), new $transformer())
            ->serializeWith(new $serializer)
            ->paginateWith(new IlluminatePaginatorAdapter($modelData))
            ->toArray();
    }

    public function getSerializer()
    {
        return $this->serializer;
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Negative error.
     *
     * @param string $message
     * @return mixed
     */
    public function respondNegativeError($message = '')
    {
        return $this->setStatusCode(404)
            ->respond([
                'status'  => 0,
                'code'    => $this->getStatusCode(),
                'message' => $this->getMessage(),
            ]);
    }

    /**
     * Positive error.
     *
     * @param string $message
     * @return mixed
     */
    public function respondPositiveError($message = '')
    {
        return $this->setStatusCode(200)
            ->respond([
                'status'  => 1,
                'code'    => $this->getStatusCode(),
                'message' => $this->getMessage(),
            ]);
    }

    public function convertPaginationResponse($response)
    {
        $data['meta'] = $response['meta'];

        unset($response['meta']);
        $data['data'] = $response;

        return $data;
    }
}

<?php

namespace App\Console\Commands;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class ElasticsearchCreateIndex extends Command
{
    protected $signature = 'es:create-index {mapping_name : Nama mapping dari config/elasticsearch_mappings.php}';
    protected $description = 'Create or recreate an Elasticsearch index with mapping from config';
    private Client $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        parent::__construct();
        $this->elasticsearch = $elasticsearch;
    }

    public function handle()
    {
        $mappingName = $this->argument('mapping_name');
        $mappingConfig = Config::get("elasticsearch_mappings.{$mappingName}");

        if (!$mappingConfig) {
            $this->error("Mapping configuration '{$mappingName}' not found in config/elasticsearch_mappings.php");
            return 1;
        }

        $indexName = $mappingName;
        $params = [
            'index' => $indexName,
            'body' => ['settings' => $mappingConfig['settings'] ?? [], 'mappings' => $mappingConfig['mappings'] ?? [],]
        ];

        try {
            $this->warn("Attempting to delete existing index/alias '{$indexName}'...");
            $deleteResponse = $this->elasticsearch->indices()->delete(['index' => $indexName])->asArray();

            if (isset($deleteResponse['acknowledged']) && $deleteResponse['acknowledged']) {
                $this->info("Existing index/alias '{$indexName}' successfully deleted (acknowledged).");
            } else {
                $this->warn("Delete command for '{$indexName}' sent, but not acknowledged immediately.");
                Log::debug("Delete response for {$indexName}", $deleteResponse);
            }
        } catch (ClientResponseException $e) {
            if ($e->getCode() === 404) {
                $this->info("Index/alias '{$indexName}' not found (404), no need to delete. Proceeding to create.");
            } else {
                $this->error("Client error occurred while attempting to delete index/alias '{$indexName}' (Code: {$e->getCode()}): " . $e->getMessage());
                Log::error("Client error deleting index/alias '{$indexName}'", ['exception' => $e]);
                return 1;
            }
        } catch (ServerResponseException $e) {
            $this->error("Server error occurred while attempting to delete index/alias '{$indexName}' (Code: {$e->getCode()}): " . $e->getMessage());
            Log::error("Server error deleting index/alias '{$indexName}'", ['exception' => $e]);
            return 1;
        } catch (NoNodeAvailableException $e) {
            Log::critical('Elasticsearch connection failed during delete attempt: No nodes available.', ['exception' => $e->getMessage()]);
            $this->error('Tidak dapat terhubung ke server Elasticsearch saat mencoba menghapus indeks.');
            return 1;
        } catch (Throwable $e) {
            $this->error("An unexpected PHP error occurred while attempting to delete index/alias '{$indexName}': " . $e->getMessage());
            Log::error("Unexpected error deleting index/alias '{$indexName}'", ['exception' => $e, 'trace_snippet' => $e->getTraceAsString()]);
            return 1;
        }

        try {
            $this->info("Creating index '{$indexName}' with new mapping...");
            $response = $this->elasticsearch->indices()->create($params)->asArray();

            $acknowledged = isset($response['acknowledged']) && $response['acknowledged'] === true;
            Log::info("Elasticsearch index '{$indexName}' creation attempt finished.", ['acknowledged' => $acknowledged]);

            if ($acknowledged) {
                $this->info("Index '{$indexName}' created successfully (acknowledged).");
                $this->comment("-----------------------------------------------------------");
                $this->comment(" IMPORTANT: Please run 'php artisan es:index-products' now!");
                $this->comment("-----------------------------------------------------------");
                return 0;
            } else {
                $this->warn("Index '{$indexName}' creation command sent, but not acknowledged. Check ES logs/status.");
                $this->comment("-----------------------------------------------------------");
                $this->comment(" IMPORTANT: Please run 'php artisan es:index-products' to populate the index.");
                $this->comment("-----------------------------------------------------------");
                return 0;
            }
        } catch (ClientResponseException $e) {
            $statusCode = $e->getCode();
            $errorMessage = $e->getMessage();
            $logContext = ['params_sent' => $params, 'exception_class' => get_class($e), 'trace_snippet' => $e->getTraceAsString()];

            if ($statusCode === 400) {
                $this->error("Failed to create index '{$indexName}' due to bad request (400). Check mapping configuration.");
                $this->error("Elasticsearch Message: " . $errorMessage);
                Log::error("Failed to create Elasticsearch index '{$indexName}' (Bad Request 400)", $logContext);
            } else {
                $this->error("Failed to create index '{$indexName}' due to client error ({$statusCode}): " . $errorMessage);
                Log::error("Elasticsearch Client Error during index creation", $logContext);
            }
            return 1;
        } catch (ServerResponseException $e) {
            $statusCode = $e->getCode();
            $this->error("Failed to create index '{$indexName}' due to server error ({$statusCode}): " . $e->getMessage());
            Log::error("Elasticsearch Server Error during index creation", ['exception' => $e, 'params' => $params, 'trace_snippet' => $e->getTraceAsString()]);
            return 1;
        } catch (NoNodeAvailableException $e) {
            Log::critical('Elasticsearch connection failed during create attempt: No nodes available.', ['exception' => $e->getMessage()]);
            $this->error('Tidak dapat terhubung ke server Elasticsearch saat mencoba membuat indeks.');
            return 1;
        } catch (Throwable $e) {
            $this->error("An unexpected PHP error occurred while creating index '{$indexName}': " . $e->getMessage());
            Log::error("Unexpected error creating index '{$indexName}'", [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'trace_snippet' => $e->getTraceAsString(),
                'params' => $params
            ]);
            return 1;
        }
    }
}

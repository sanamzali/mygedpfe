<?php

namespace App\Services;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticDocumentService
{
    private $client;
    private $indexName = 'docs';

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['http://localhost:9200'])
            ->build();
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0
                ],
                'mappings' => [
                    'properties' => [
                        'space_name' => ['type' => 'text'],
                        'folder_name' => ['type' => 'text'],
                        'project_name' => ['type' => 'text'],
                        'filename' => ['type' => 'text'],
                        'content' => ['type' => 'text'],
                        'file_path' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date']
                    ]
                ]
            ]
        ];

        try {
            $response = $this->client->indices()->create($params);
            Log::info("Created index $this->indexName: " . json_encode($response));
        } catch (\Exception $e) {
            if ($e->getCode() !== 400) { // Ignore if index already exists
                Log::error("Failed to create index $this->indexName: " . $e->getMessage());
            }
        }
    }

    public function indexDocument($file)
    {
        $content = $this->extractFileContent($file->storage_path);

        $params = [
            'index' => $this->indexName,
            'body' => [
                'space_name' => $file->folder->space->name ?? null,
                'folder_name' => $file->folder->name ?? null,
                'project_name' => $file->folder->project->name ?? null,
                'filename' => $file->filename,
                'content' => $content,
                'file_path' => $file->storage_path,
                'created_at' => $file->created_at->toIso8601String()
            ]
        ];

        try {
            $response = $this->client->index($params);
            Log::info("Indexed document: " . json_encode($response));
        } catch (\Exception $e) {
            Log::error("Failed to index document: " . $e->getMessage());
        }
    }

    private function extractFileContent($path)
    {
        $fullPath = storage_path('app/' . $path);
        if (file_exists($fullPath)) {
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
            if ($extension === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($fullPath);
                return $pdf->getText();
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                $content = '';
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        foreach ($cellIterator as $cell) {
                            $content .= ' ' . $cell->getValue();
                        }
                    }
                }
                return trim($content);
            }
        }
        return ''; // Return empty for unsupported formats
    }

    public function search($query)
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['space_name', 'folder_name', 'project_name', 'filename', 'content']
                    ]
                ]
            ]
        ];

        try {
            return $this->client->search($params);
        } catch (\Exception $e) {
            Log::error("Search failed: " . $e->getMessage());
            return ['hits' => ['hits' => []]];
        }
    }
}
<?php

namespace App\Services;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class UserExportService
{
    /**
     * Get filtered users query with relationships
     */
    private function getUsersQuery(?string $search = null)
    {
        return User::query()
            ->with(['profile', 'contact.warehouse.company', 'contact.position'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Format user data for export
     */
    private function formatUserData($users): array
    {
        $data = [];
        
        foreach ($users as $user) {
            $contact = $user->contact;
            $fullName = '';
            $warehouse = '';
            $company = '';
            $position = '';
            
            if ($contact) {
                $nameParts = array_filter([
                    $contact->firstName,
                    $contact->secondName,
                    $contact->lastName,
                    $contact->secondLastName
                ]);
                $fullName = implode(' ', $nameParts);
                
                if ($contact->warehouse) {
                    $warehouse = $contact->warehouse->name ?? '';
                    $company = $contact->warehouse->company->name ?? '';
                }
                
                $position = $contact->position->name ?? '';
            }
            
            $data[] = [
                'full_name' => $fullName ?: $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'profile' => $user->profile->name ?? '',
                'warehouse' => $warehouse,
                'company' => $company,
                'position' => $position,
            ];
        }
        
        return $data;
    }

    /**
     * Export users to Excel format
     */
    public function exportToExcel(?string $search = null): array
    {
        try {
            $users = $this->getUsersQuery($search);
            
            if ($users->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No hay usuarios para exportar'
                ];
            }
            
            $data = $this->formatUserData($users);
            $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';
            
            // Create Excel XML content
            $content = $this->generateExcelXML($data);
            
            // Return download response
            return [
                'success' => true,
                'message' => 'Exportación exitosa',
                'download' => Response::make($content, 200, [
                    'Content-Type' => 'application/vnd.ms-excel',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Cache-Control' => 'max-age=0',
                ])
            ];
            
        } catch (\Exception $e) {
            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al exportar a Excel: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export users to PDF format
     */
    public function exportToPdf(?string $search = null): array
    {
        try {
            $users = $this->getUsersQuery($search);
            
            if ($users->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No hay usuarios para exportar'
                ];
            }
            
            $data = $this->formatUserData($users);
            $filename = 'usuarios_' . date('Y-m-d_His') . '.pdf';
            
            // Generate HTML content for PDF
            $html = $this->generatePdfHtml($data);
            
            // For now, return HTML as PDF (basic implementation)
            // In production, you would use a library like dompdf or wkhtmltopdf
            return [
                'success' => true,
                'message' => 'Exportación exitosa',
                'download' => Response::make($html, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Cache-Control' => 'max-age=0',
                ])
            ];
            
        } catch (\Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al exportar a PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export users to CSV format
     */
    public function exportToCsv(?string $search = null): array
    {
        try {
            $users = $this->getUsersQuery($search);
            
            if ($users->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No hay usuarios para exportar'
                ];
            }
            
            $data = $this->formatUserData($users);
            $filename = 'usuarios_' . date('Y-m-d_His') . '.csv';
            
            // Generate CSV content
            $content = $this->generateCsv($data);
            
            // Return download response
            return [
                'success' => true,
                'message' => 'Exportación exitosa',
                'download' => Response::make($content, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Cache-Control' => 'max-age=0',
                ])
            ];
            
        } catch (\Exception $e) {
            Log::error('CSV export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al exportar a CSV: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate CSV content from data
     */
    private function generateCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, [
            'Nombre Completo',
            'Email',
            'Teléfono',
            'Perfil',
            'Sucursal',
            'Empresa',
            'Cargo'
        ]);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['profile'],
                $row['warehouse'],
                $row['company'],
                $row['position'],
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Generate Excel XML content from data
     */
    private function generateExcelXML(array $data): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Usuarios">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Add header row
        $xml .= '<Row>' . "\n";
        $headers = [
            'Nombre Completo',
            'Email',
            'Teléfono',
            'Perfil',
            'Sucursal',
            'Empresa',
            'Cargo'
        ];
        
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Add data rows
        foreach ($data as $row) {
            $xml .= '<Row>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['full_name']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['email']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['phone']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['profile']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['warehouse']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['company']) . '</Data></Cell>' . "\n";
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($row['position']) . '</Data></Cell>' . "\n";
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }

    /**
     * Generate HTML content for PDF
     */
    private function generatePdfHtml(array $data): string
    {
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html>' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<title>Listado de Usuarios</title>' . "\n";
        $html .= '<style>' . "\n";
        $html .= 'body { font-family: Arial, sans-serif; font-size: 12px; }' . "\n";
        $html .= 'h1 { text-align: center; color: #333; }' . "\n";
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }' . "\n";
        $html .= 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }' . "\n";
        $html .= 'th { background-color: #4CAF50; color: white; }' . "\n";
        $html .= 'tr:nth-child(even) { background-color: #f2f2f2; }' . "\n";
        $html .= '</style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<h1>Listado de Usuarios</h1>' . "\n";
        $html .= '<p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>' . "\n";
        $html .= '<table>' . "\n";
        $html .= '<thead>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<th>Nombre Completo</th>' . "\n";
        $html .= '<th>Email</th>' . "\n";
        $html .= '<th>Teléfono</th>' . "\n";
        $html .= '<th>Perfil</th>' . "\n";
        $html .= '<th>Sucursal</th>' . "\n";
        $html .= '<th>Empresa</th>' . "\n";
        $html .= '<th>Cargo</th>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '</thead>' . "\n";
        $html .= '<tbody>' . "\n";
        
        foreach ($data as $row) {
            $html .= '<tr>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['full_name']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['email']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['phone']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['profile']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['warehouse']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['company']) . '</td>' . "\n";
            $html .= '<td>' . htmlspecialchars($row['position']) . '</td>' . "\n";
            $html .= '</tr>' . "\n";
        }
        
        $html .= '</tbody>' . "\n";
        $html .= '</table>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';
        
        return $html;
    }
}

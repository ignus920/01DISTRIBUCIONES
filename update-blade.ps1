# Script para actualizar el archivo blade con la funcionalidad de usuario existente

$filePath = "resources/views/livewire/tenant/vnt-company/components/vnt-company-form.blade.php"
$content = Get-Content $filePath -Raw

# Reemplazo 1: Eliminar el @if(!$editingId) y @endif
$content = $content -replace '<!-- Crear Usuario Checkbox \(solo para nuevos clientes\) -->', '<!-- Crear Usuario Checkbox -->'
$content = $content -replace '@if\(!\\$editingId\)\s+<div class="md:col-span-2">', '<div class="md:col-span-2">'

# Reemplazo 2: Agregar || $hasExistingUser en todas las condiciones
$content = $content -replace '\{\{ empty\(\\$billingEmail\) \|\| \\$emailExists \?', '{{ empty($billingEmail) || $emailExists || $hasExistingUser ?'

# Reemplazo 3: Agregar el caso @elseif($hasExistingUser)
$oldText = '@elseif($emailExists)
                                        No disponible: el email ya está registrado
                                        @else'
$newText = '@elseif($emailExists)
                                        No disponible: el email ya está registrado
                                        @elseif($hasExistingUser)
                                        Este cliente ya tiene un usuario asignado ({{ $existingUserEmail }})
                                        @else'
$content = $content -replace [regex]::Escape($oldText), $newText

# Reemplazo 4: Agregar el badge condicional
$oldBadge = 'Perfil: Tienda
                                </div>
                            </div>
                        </div>
                        @endif'
$newBadge = '@if($hasExistingUser)
                                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Usuario Existente
                                    @else
                                    Perfil: Tienda
                                    @endif
                                </div>
                            </div>
                        </div>'
$content = $content -replace [regex]::Escape($oldBadge), $newBadge

# Guardar el archivo
Set-Content $filePath -Value $content -NoNewline

Write-Host "Archivo actualizado exitosamente!"

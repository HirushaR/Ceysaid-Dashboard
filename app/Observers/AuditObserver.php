<?php
namespace App\Observers;
use App\Services\AuditService;use Illuminate\Database\Eloquent\Model;
class AuditObserver{public function created(Model $m):void{$this->write('created',$m,[],$m->getAttributes());}public function updated(Model $m):void{$changes=$m->getChanges();$old=collect($changes)->mapWithKeys(fn($v,$k)=>[$k=>$m->getOriginal($k)])->all();$this->write('updated',$m,$old,$changes);}public function deleted(Model $m):void{$this->write('deleted',$m,$m->getOriginal(),[]);}private function write(string $event,Model $model,array $old,array $new):void{app(AuditService::class)->record(class_basename($model).'.'.$event,$model,$old,$new,request()?->input('audit_reason'));}}

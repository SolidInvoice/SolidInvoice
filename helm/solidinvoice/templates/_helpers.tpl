{{/*
Expand the name of the chart.
*/}}
{{- define "solidinvoice.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
We truncate at 63 chars because some Kubernetes name fields are limited to this (by the DNS naming spec).
If release name contains chart name it will be used as a full name.
*/}}
{{- define "solidinvoice.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "solidinvoice.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "solidinvoice.labels" -}}
helm.sh/chart: {{ include "solidinvoice.chart" . }}
{{ include "solidinvoice.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "solidinvoice.selectorLabels" -}}
app.kubernetes.io/name: {{ include "solidinvoice.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "solidinvoice.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "solidinvoice.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
Return the image name
*/}}
{{- define "solidinvoice.imageName" -}}
{{- printf "%s:%s" .Values.image.repository (default .Chart.AppVersion .Values.image.tag) }}
{{- end }}

{{/*
Return the PVC name for /etc/solidinvoice config volume
*/}}
{{- define "solidinvoice.pvcName" -}}
{{- if .Values.persistence.existingClaim }}
{{- .Values.persistence.existingClaim }}
{{- else }}
{{- printf "%s-config" (include "solidinvoice.fullname" .) }}
{{- end }}
{{- end }}

{{/*
Config volume definition (used across app, worker, jobs, cronjob)
Returns the volume spec entry for the solidinvoice-config volume.
*/}}
{{- define "solidinvoice.configVolume" -}}
- name: solidinvoice-config
  persistentVolumeClaim:
    claimName: {{ include "solidinvoice.pvcName" . }}
{{- end }}

{{/*
Config volumeMount definition (used across app, worker, jobs, cronjob)
Returns the volumeMount entry mounting /etc/solidinvoice.
*/}}
{{- define "solidinvoice.configVolumeMount" -}}
- name: solidinvoice-config
  mountPath: /etc/solidinvoice
{{- end }}

{{/*
Return the name of the MySQL secret containing the password.
*/}}
{{- define "solidinvoice.mysql.secretName" -}}
{{- printf "%s-mysql" .Release.Name }}
{{- end }}

{{/*
Return the name of the PostgreSQL secret containing the password.
*/}}
{{- define "solidinvoice.postgresql.secretName" -}}
{{- printf "%s-postgresql" .Release.Name }}
{{- end }}

{{/*
Return the name of the Redis secret containing the password.
*/}}
{{- define "solidinvoice.redis.secretName" -}}
{{- printf "%s-redis" .Release.Name }}
{{- end }}

{{/*
Return the name of the Meilisearch secret containing the master key.
*/}}
{{- define "solidinvoice.meilisearch.secretName" -}}
{{- printf "%s-meilisearch" .Release.Name }}
{{- end }}

{{/*
Return the MySQL host
*/}}
{{- define "solidinvoice.mysql.host" -}}
{{- printf "%s-mysql" .Release.Name }}
{{- end }}

{{/*
Return the PostgreSQL host
*/}}
{{- define "solidinvoice.postgresql.host" -}}
{{- printf "%s-postgresql" .Release.Name }}
{{- end }}

{{/*
Return the Redis master host
*/}}
{{- define "solidinvoice.redis.host" -}}
{{- printf "%s-redis-master" .Release.Name }}
{{- end }}

{{/*
Return the Meilisearch host URL
*/}}
{{- define "solidinvoice.meilisearch.url" -}}
{{- printf "http://%s-meilisearch:7700" .Release.Name }}
{{- end }}

{{/*
Worker selector labels - distinguishes worker pods from app pods
*/}}
{{- define "solidinvoice.worker.selectorLabels" -}}
app.kubernetes.io/name: {{ include "solidinvoice.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/component: worker
{{- end }}

{{/*
Worker labels
*/}}
{{- define "solidinvoice.worker.labels" -}}
helm.sh/chart: {{ include "solidinvoice.chart" . }}
{{ include "solidinvoice.worker.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

# Wtyczka Media Redirect to Production

## Instalacja

Wtyczkę dodajemy do repozytorium projektu, jak każdą inną wtyczkę, czyli do katalogu `plugins`.

W przypadku Bedrocka dodajemy wyjątek w `.gitignore`:

```text
!web/app/plugins/media-redirect
```

Wgrywamy katalog `media-redirect` i jego zawartość. Nie dodajemy katalogu `.git` z tego repozytorium.

## Uruchomienie

Po włączeniu wtyczki możemy ją skonfigurować w panelu WordPress: `Kokpit > Ustawienia > Media Redirect`.

Opcja `Preferuj lokalne pliki z uploads` powoduje, że lokalny URL zostaje zachowany, jeśli plik istnieje fizycznie w katalogu `uploads`. Przekierowanie na produkcję jest wtedy używane tylko dla brakujących plików.

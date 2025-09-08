# POS Selection Service

Bu uygulama, e-ticaret ödeme anında müşterinin kart bilgilerine göre en düşük maliyetli POS sağlayıcısını seçip işlemi o POS'a yönlendiren bir servistir.

## Kullanılan Teknolojiler

- **Backend:** PHP 8.1, Symfony 5.4
- **Altyapı:** Docker, Docker Compose, Nginx, Redis
- **Symfony Bileşenleri:** Messenger (Kuyruk), Cache, HTTP Client, Monolog (Loglama)


## Kurulum Talimatları

1. Projeyi klonlayın:
   ```bash
   git clone https://github.com/fatihmehmetdogan/pos-selection-service.git
   cd pos-selection-service
   ```

2. Docker container'larını başlatın:
   ```bash
   docker compose up -d --build
   ```

3.  Composer bağımlılıklarını yükleyin:
   ```bash
   docker compose exec app composer install
   ```

4.POS oranlarını çekin :
   Uygulamanın çalışması için gerekli olan veriyi ilk kez çekmek üzere aşağıdaki komutu çalıştırın. Bu komut, kuyruğa bir görev ekler ve worker bu görevi işleyerek veriyi günceller.
   ```bash
   docker compose exec app php bin/console app:refresh-pos-ratios
   ```

## Çalıştırma Adımları

### API Kullanımı

API, `http://localhost:8080/api/pos/select` endpoint'ine HTTP POST isteği gönderilerek kullanılabilir:

### Örnek İstekler

#### Örnek 1: TRY, credit, 6 taksit
```bash
curl -X POST http://localhost:8080/api/pos/select \
  -H "Content-Type: application/json" \
  -d '{"amount": 362.22, "installment": 6, "currency": "TRY", "card_type": "credit"}'
```

**JSON Request:**
```json
{
  "amount": 362.22,
  "installment": 6,
  "currency": "TRY",
  "card_type": "credit"
}
```

**Yanıt:**
```json
{
  "filters": {
    "amount": 362.22,
    "installment": 6,
    "currency": "TRY",
    "card_type": "credit",
    "card_brand": null
  },
  "overall_min": {
    "pos_name": "KuveytTurk",
    "card_type": "credit",
    "card_brand": "saglam",
    "installment": 6,
    "currency": "TRY",
    "commission_rate": "0.0260",
    "price": 9.42,
    "payable_total": 371.64
  }
}
```


### POS Oranlarını Manuel Güncelleme

POS oranlarını manuel olarak güncellemek için Docker container içinde şu komutu çalıştırabilirsiniz:

```bash
docker compose exec app bash -c "php bin/console app:refresh-pos-ratios"
```

Not: POS oranları, otomatik olarak her gün 23:59 (Europe/Istanbul) saatinde cron job ile güncellenir.


package com.example.bt.interfaces;

import androidx.room.Dao;
import androidx.room.Insert;
import androidx.room.OnConflictStrategy;
import androidx.room.Query;

import com.example.bt.push.PushData;

@Dao
public interface PushDataDao {

    @Insert(onConflict = OnConflictStrategy.IGNORE)
    void insert(PushData push);

    @Query("SELECT MAX(id) FROM PushData")
    int getLastId();

    @Query("SELECT id FROM PushData WHERE type = :type")
    int[] getByType(String type);

    @Query("SELECT id FROM PushData WHERE type = :type AND data = :data")
    int[] getByData(String type, String data);

    @Query("SELECT id FROM PushData WHERE type = :type AND data = :data AND params = :params")
    int getByParams(String type, String data, String params);

    @Query("SELECT * FROM PushData")
    PushData[] getAll();

    @Query("DELETE FROM PushData WHERE id = :id")
    void deleteById(int id);

    @Query("DELETE FROM PushData WHERE type = :type")
    void deleteByType(String type);

    @Query("DELETE FROM PushData WHERE type = :type AND data = :data")
    void deleteByData(String type, String data);

    @Query("DELETE FROM PushData")
    void deleteAll();
}
